<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\OrderCreated;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderQueue;
use App\Services\GroqService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class VoiceOrderController extends Controller
{
    public function __construct(private readonly GroqService $groqService) {}

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'raw_text' => ['required', 'string', 'max:500'],
            'customer_name' => ['required', 'string', 'max:100'],
            'gender' => ['nullable', 'in:male,female,other'],
        ]);

        return $this->processOrderFromText(
            rawText: $payload['raw_text'],
            customerName: $payload['customer_name'],
            gender: $payload['gender'] ?? null,
        );
    }

    public function transcribe(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'audio' => ['required', 'file', 'mimetypes:audio/wav,audio/x-wav,audio/mpeg,audio/mp4,audio/webm,audio/ogg', 'max:20480'],
            'auto_order' => ['nullable', 'boolean'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'in:male,female,other'],
        ]);

        $audio = $payload['audio'];
        $text = $this->groqService->transcribeAudio(
            filePath: $audio->getRealPath(),
            originalName: $audio->getClientOriginalName() ?: 'audio.wav',
        );

        if ($text === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Transkripsi gagal. Coba ulangi atau kirim raw_text langsung.',
            ], 422);
        }

        if ($request->boolean('auto_order')) {
            if (trim((string) ($payload['customer_name'] ?? '')) === '') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nama pelanggan wajib diisi untuk auto order.',
                ], 422);
            }

            return $this->processOrderFromText(
                rawText: $text,
                customerName: (string) $payload['customer_name'],
                gender: $payload['gender'] ?? null,
            );
        }

        return response()->json([
            'status' => 'ok',
            'raw_text' => $text,
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'raw_text' => ['required', 'string', 'max:500'],
        ]);

        $analysis = $this->analyzeOrderText($payload['raw_text']);
        $validated = $analysis['validated'];

        if ($validated['status'] === 'invalid') {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Pesanan tidak dikenali, silakan ulangi.',
                'items' => [],
            ], 422);
        }

        if ($validated['status'] === 'partial') {
            return response()->json([
                'status' => 'partial',
                'message' => 'Sebagian menu tidak tersedia: '.implode(', ', $validated['invalid']),
                'items' => collect($validated['valid_items'])
                    ->map(fn (array $item): array => [
                        'name' => $item['menu']->name,
                        'qty' => $item['qty'],
                    ])
                    ->values()
                    ->all(),
                'invalid' => $validated['invalid'],
            ]);
        }

        return response()->json([
            'status' => 'valid',
            'message' => 'Pesanan siap dikonfirmasi.',
            'items' => collect($validated['valid_items'])
                ->map(fn (array $item): array => [
                    'name' => $item['menu']->name,
                    'qty' => $item['qty'],
                ])
                ->values()
                ->all(),
            'confidence' => $analysis['parsed']['confidence'] ?? 'low',
        ]);
    }

    private function processOrderFromText(string $rawText, string $customerName, ?string $gender = null): JsonResponse
    {
        $analysis = $this->analyzeOrderText($rawText);
        $rawText = $analysis['raw_text'];
        $normalizedText = $analysis['normalized_text'];
        $parsed = $analysis['parsed'];
        $validated = $analysis['validated'];

        if ($validated['status'] === 'invalid') {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Pesanan tidak dikenali, silakan ulangi.',
                'items' => [],
            ], 422);
        }

        if ($validated['status'] === 'partial') {
            return response()->json([
                'status' => 'partial',
                'message' => 'Menu '.implode(', ', $validated['invalid']).' tidak tersedia, lanjutkan dengan '.implode(', ', $validated['valid_names']).' saja?',
                'valid' => $validated['valid_names'],
                'invalid' => $validated['invalid'],
            ]);
        }

        $result = DB::transaction(function () use ($rawText, $normalizedText, $validated, $parsed, $customerName, $gender): array {
            $order = Order::create([
                'customer_name' => $customerName,
                'gender' => $gender,
                'raw_text' => $rawText,
                'normalized_text' => $normalizedText,
                'source' => 'voice',
                'parsing_confidence' => $parsed['confidence'],
                'validation_status' => 'valid',
                'status' => 'waiting',
                'total_amount' => 0,
            ]);

            $total = 0;

            foreach ($validated['valid_items'] as $item) {
                $unitPrice = (float) $item['menu']->price;
                $subtotal = $unitPrice * $item['qty'];
                $total += $subtotal;

                $order->items()->create([
                    'menu_id' => $item['menu']->id,
                    'item_name' => $item['menu']->name,
                    'qty' => $item['qty'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update([
                'total_amount' => $total,
            ]);

            $queue = OrderQueue::create([
                'order_id' => $order->id,
                'status' => 'waiting',
            ]);

            $payloadItems = $order->items()
                ->get(['item_name', 'qty'])
                ->map(fn ($item) => [
                    'name' => $item->item_name,
                    'qty' => $item->qty,
                ])
                ->values()
                ->all();

            event(new OrderCreated(
                orderId: $order->id,
                queueNumber: $queue->queue_number,
                items: $payloadItems,
                rawText: $rawText,
            ));

            return [
                'order' => $order,
                'queue' => $queue,
                'items' => $payloadItems,
            ];
        });

        return response()->json([
            'status' => 'valid',
            'order_id' => $result['order']->id,
            'order_code' => $result['order']->order_code,
            'queue_number' => $result['queue']->queue_number,
            'items' => $result['items'],
            'message' => 'Pesanan Anda: '.$this->readableItemList($result['items']).', nomor antrian '.$result['queue']->queue_number.'.',
        ], 201);
    }

    /**
     * @return array{raw_text:string,normalized_text:string,parsed:array{items:array<int,array{name:string,qty:int}>,confidence:string},validated:array<string,mixed>}
     */
    private function analyzeOrderText(string $rawText): array
    {
        $rawText = Str::of($rawText)->squish()->toString();
        $normalizedText = $this->normalizeText($rawText);
        $rulesParsed = $this->parseByRules($normalizedText);
        $aiItems = $this->parseWithValidationModel($normalizedText);
        $parsed = [
            'items' => $this->mergeParsedItemsPreferHigherQty($aiItems, $rulesParsed['items']),
            'confidence' => 'high',
        ];

        if (empty($aiItems)) {
            $parsed = $rulesParsed;
            $parsed['confidence'] = empty($parsed['items']) ? 'low' : 'fallback';
        }

        return [
            'raw_text' => $rawText,
            'normalized_text' => $normalizedText,
            'parsed' => $parsed,
            'validated' => $this->validateItems($parsed['items']),
        ];
    }

    private function normalizeText(string $rawText): string
    {
        $text = Str::of($rawText)->lower()->squish()->toString();

        $noiseWords = [
            'saya mau',
            'aku mau',
            'saya pesan',
            'aku pesan',
            'mau',
            'pesan',
            'tolong',
            'dong',
            'ya',
            'kak',
            'oke',
        ];

        foreach ($noiseWords as $noiseWord) {
            $pattern = '/\b'.str_replace(' ', '\\s+', preg_quote($noiseWord, '/')).'\b/';
            $text = preg_replace($pattern, ' ', $text) ?? $text;
        }

        $text = preg_replace('/\b(?:tambah(?:kan)?(?:\s+juga)?|terus|lalu|habis\s+itu|abis\s+itu|sama\s+yang)\b/', ',', $text) ?? $text;
        $text = preg_replace('/\bteh\s+teh\s+manis\s+dingin\b/', 'teh, teh manis dingin', $text) ?? $text;
        $text = preg_replace('/\b(\d+)\s+lagi\b/', '$1', $text) ?? $text;
        $text = preg_replace('/\blagi\b/', ' ', $text) ?? $text;
        $text = preg_replace('/\b(?:eh|biasa)\b/', ' ', $text) ?? $text;

        $numberWordMap = [
            'sepuluh' => '10',
            'sembilan' => '9',
            'delapan' => '8',
            'tujuh' => '7',
            'enam' => '6',
            'lima' => '5',
            'empat' => '4',
            'tiga' => '3',
            'dua' => '2',
            'satu' => '1',
        ];

        foreach ($numberWordMap as $word => $number) {
            $text = preg_replace('/\b'.$word.'\b/', $number, $text) ?? $text;
        }

        $text = preg_replace('/\b([a-z0-9]{3,})nya\b/', '$1', $text) ?? $text;

        return Str::of($text)
            ->replaceMatches('/[^a-z0-9,\s]/', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }

    private function parseByRules(string $normalizedText): array
    {
        if ($normalizedText === '') {
            return [
                'items' => [],
                'confidence' => 'low',
            ];
        }

        $slangMap = [
            'nasgor' => 'nasi goreng',
            'teh anget' => 'teh',
            'es teh manis' => 'teh manis dingin',
        ];

        $segments = preg_split('/\s*(?:,| dan | sama | plus |\+| terus | lalu | tambah(?:kan)?(?: juga)?)\s*/', $normalizedText) ?: [];

        $items = [];

        foreach ($segments as $segment) {
            $segment = trim($segment);

            if ($segment === '') {
                continue;
            }

            $segment = $this->normalizeNumberWords($segment);
            $segment = $this->normalizePossessiveSuffix($segment);
            foreach ($this->extractItemsFromSegment($segment, $slangMap) as $item) {
                $items[] = $item;
            }
        }

        return [
            'items' => $items,
            'confidence' => empty($items) ? 'low' : 'high',
        ];
    }

    private function extractQtyAndName(string $segment): array
    {
        $qty = 1;
        $name = $segment;

        if (preg_match('/^(\d+)\s*(?:x|kali)?\s+(.+)$/', $segment, $match)) {
            $qty = max(1, (int) $match[1]);
            $name = trim($match[2]);

            return [
                'name' => $name,
                'qty' => $qty,
            ];
        }

        if (preg_match('/^(.+?)\s+(?:x\s*)?(\d+)\s*(?:x|kali)?$/', $segment, $match)) {
            $name = trim($match[1]);
            $qty = max(1, (int) $match[2]);
        }

        return [
            'name' => $name,
            'qty' => $qty,
        ];
    }

    private function extractItemsFromSegment(string $segment, array $slangMap): array
    {
        $segment = Str::of($segment)
            ->replaceMatches('/\bx(\d+)\b/', '$1')
            ->replaceMatches('/\b(\d+)x\b/', '$1')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();

        $items = [];

        if (preg_match('/^\d+/', $segment)) {
            preg_match_all('/(\d+)\s*(?:x|kali)?\s+([a-z0-9\s]+?)(?=(?:\s+\d+\s*(?:x|kali)?\s+[a-z]|$))/', $segment, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $name = Str::of($match[2])
                    ->replaceMatches('/\b(?:x|kali)\b/', ' ')
                    ->replaceMatches('/\s+/', ' ')
                    ->trim()
                    ->toString();
                $name = $slangMap[$name] ?? $name;
                if ($name !== '') {
                    $items[] = [
                        'name' => $name,
                        'qty' => max(1, (int) $match[1]),
                    ];
                }
            }
        } else {
            preg_match_all('/([a-z0-9\s]+?)\s+(?:x\s*)?(\d+)\s*(?:x|kali)?(?=(?:\s+[a-z][a-z0-9\s]*?\s+(?:x\s*)?\d+\s*(?:x|kali)?|$))/', $segment, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $name = Str::of($match[1])
                    ->replaceMatches('/\b(?:x|kali)\b/', ' ')
                    ->replaceMatches('/\s+/', ' ')
                    ->trim()
                    ->toString();
                $name = $slangMap[$name] ?? $name;
                if ($name !== '') {
                    $items[] = [
                        'name' => $name,
                        'qty' => max(1, (int) $match[2]),
                    ];
                }
            }
        }

        if (! empty($items)) {
            return $items;
        }

        ['name' => $name, 'qty' => $qty] = $this->extractQtyAndName($segment);
        $name = Str::of($name)
            ->replaceMatches('/\b(?:x|kali)\b/', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
        $name = $slangMap[$name] ?? $name;

        if ($name === '') {
            return [];
        }

        return [[
            'name' => $name,
            'qty' => $qty,
        ]];
    }

    private function normalizeNumberWords(string $segment): string
    {
        $map = [
            'sepuluh' => '10',
            'sembilan' => '9',
            'delapan' => '8',
            'tujuh' => '7',
            'enam' => '6',
            'lima' => '5',
            'empat' => '4',
            'tiga' => '3',
            'dua' => '2',
            'satu' => '1',
            'se' => '1',
        ];

        foreach ($map as $word => $number) {
            $segment = preg_replace('/\b'.$word.'\b/', $number, $segment) ?? $segment;
        }

        return Str::of($segment)
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }

    private function normalizePossessiveSuffix(string $text): string
    {
        return Str::of($text)
            // Convert conversational suffix forms like "tehnya" -> "teh".
            ->replaceMatches('/\b([a-z0-9]{3,})nya\b/', '$1')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }

    private function parseWithValidationModel(string $normalizedText): array
    {
        if ($normalizedText === '') {
            return [];
        }

        try {
            return $this->groqService->parseOrderItems($normalizedText);
        } catch (Throwable $exception) {
            Log::warning('Groq primary parse failed', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @param  array<int, array{name:string,qty:int}>  $primary
     * @param  array<int, array{name:string,qty:int}>  $secondary
     * @return array<int, array{name:string,qty:int}>
     */
    private function mergeParsedItemsPreferHigherQty(array $primary, array $secondary): array
    {
        $merged = [];
        $primaryNames = [];

        foreach ($primary as $item) {
            $name = Str::of((string) ($item['name'] ?? ''))->lower()->squish()->toString();
            $qty = max(1, (int) ($item['qty'] ?? 1));

            if ($name === '') {
                continue;
            }

            $primaryNames[] = $name;

            if (! isset($merged[$name])) {
                $merged[$name] = $qty;
                continue;
            }

            $merged[$name] = max($merged[$name], $qty);
        }

        foreach ($secondary as $item) {
            $name = Str::of((string) ($item['name'] ?? ''))->lower()->squish()->toString();
            $qty = max(1, (int) ($item['qty'] ?? 1));

            if ($name === '') {
                continue;
            }

            $wordCount = str_word_count($name);
            $containsPrimaryCount = 0;
            foreach ($primaryNames as $primaryName) {
                if ($primaryName !== '' && str_contains($name, $primaryName)) {
                    $containsPrimaryCount++;
                }
            }

            // Drop noisy rule segments that concatenate many detected primary items.
            if ($wordCount >= 5 && $containsPrimaryCount >= 2) {
                continue;
            }

            $targetName = $name;
            if (! isset($merged[$targetName])) {
                $shorthandMatches = array_values(array_filter(
                    $primaryNames,
                    fn (string $primaryName): bool => $primaryName === $name || Str::endsWith($primaryName, ' '.$name)
                ));

                if (count($shorthandMatches) === 1) {
                    $targetName = $shorthandMatches[0];
                }
            }

            if (! isset($merged[$targetName])) {
                $merged[$targetName] = $qty;
                continue;
            }

            if ($targetName !== $name) {
                $merged[$targetName] += $qty;
                continue;
            }

            $merged[$targetName] = max($merged[$targetName], $qty);
        }

        foreach (array_keys($merged) as $name) {
            if (! isset($merged[$name]) || str_word_count($name) !== 1) {
                continue;
            }

            $specificMatches = array_values(array_filter(
                array_keys($merged),
                fn (string $other): bool => $other !== $name && Str::endsWith($other, ' '.$name)
            ));

            if (count($specificMatches) !== 1) {
                continue;
            }

            $targetName = $specificMatches[0];
            $merged[$targetName] += $merged[$name];
            unset($merged[$name]);
        }

        return collect($merged)
            ->map(fn (int $qty, string $name): array => [
                'name' => $name,
                'qty' => $qty,
            ])
            ->values()
            ->all();
    }

    private function validateItems(array $parsedItems): array
    {
        if (empty($parsedItems)) {
            return [
                'status' => 'invalid',
                'valid_items' => [],
                'valid_names' => [],
                'invalid' => [],
            ];
        }

        $menus = Menu::query()
            ->where('is_active', true)
            ->with('aliases:id,menu_id,normalized_alias')
            ->get(['id', 'name', 'price']);

        $nameIndex = $menus->keyBy(fn (Menu $menu) => Str::lower($menu->name));
        $aliasIndex = $menus
            ->flatMap(function (Menu $menu): Collection {
                return $menu->aliases->mapWithKeys(function ($alias) use ($menu): array {
                    return [Str::lower($alias->normalized_alias) => $menu];
                });
            });

        $validatedItems = [];
        $invalidItems = [];

        foreach ($parsedItems as $parsedItem) {
            $candidate = Str::of($parsedItem['name'])->lower()->squish()->toString();
            $candidate = $this->normalizePossessiveSuffix($candidate);
            $qty = max(1, (int) ($parsedItem['qty'] ?? 1));
            $menu = $nameIndex->get($candidate) ?? $aliasIndex->get($candidate);

            if (! $menu) {
                $compositeItems = $this->resolveCompositeMenuCandidate($candidate, $nameIndex, $aliasIndex);
                if (! empty($compositeItems)) {
                    foreach ($compositeItems as $compositeItem) {
                        $validatedItems[] = [
                            'menu' => $compositeItem['menu'],
                            'qty' => $qty * $compositeItem['qty'],
                        ];
                    }

                    continue;
                }
            }

            if (! $menu) {
                $repeated = $this->resolveRepeatedMenuCandidate($candidate, $nameIndex, $aliasIndex);
                if ($repeated) {
                    $menu = $repeated['menu'];
                    $qty *= $repeated['repeat'];
                }
            }

            if (! $menu) {
                $menu = $this->findByFuzzyMatch($candidate, $menus);
            }

            if (! $menu) {
                $aiMatchedName = $this->groqService->matchMenuCandidate($candidate, $menus->pluck('name')->all());

                if ($aiMatchedName) {
                    $menu = $nameIndex->get($aiMatchedName);
                }
            }

            if (! $menu) {
                $invalidItems[] = $parsedItem['name'];
                continue;
            }

            $validatedItems[] = [
                'menu' => $menu,
                'qty' => $qty,
            ];
        }

        $grouped = collect($validatedItems)
            ->groupBy(fn (array $item) => $item['menu']->id)
            ->map(function (Collection $group): array {
                $menu = $group->first()['menu'];

                return [
                    'menu' => $menu,
                    'qty' => $group->sum('qty'),
                ];
            })
            ->values()
            ->all();

        if (empty($grouped)) {
            return [
                'status' => 'invalid',
                'valid_items' => [],
                'valid_names' => [],
                'invalid' => $invalidItems,
            ];
        }

        if (! empty($invalidItems)) {
            return [
                'status' => 'partial',
                'valid_items' => $grouped,
                'valid_names' => collect($grouped)->map(fn (array $item) => $item['menu']->name)->values()->all(),
                'invalid' => $invalidItems,
            ];
        }

        return [
            'status' => 'valid',
            'valid_items' => $grouped,
            'valid_names' => collect($grouped)->map(fn (array $item) => $item['menu']->name)->values()->all(),
            'invalid' => [],
        ];
    }

    private function resolveRepeatedMenuCandidate(string $candidate, Collection $nameIndex, Collection $aliasIndex): ?array
    {
        $keys = $nameIndex->keys()
            ->merge($aliasIndex->keys())
            ->unique()
            ->sortByDesc(fn (string $value) => strlen($value))
            ->values();

        foreach ($keys as $key) {
            $quoted = preg_quote($key, '/');
            $pattern = '/^(?:'.$quoted.')(?:\s+'.$quoted.')+$/';

            if (! preg_match($pattern, $candidate)) {
                continue;
            }

            preg_match_all('/'.$quoted.'/', $candidate, $matches);
            $repeat = count($matches[0]);

            if ($repeat < 2) {
                continue;
            }

            $menu = $nameIndex->get($key) ?? $aliasIndex->get($key);
            if (! $menu) {
                continue;
            }

            return [
                'menu' => $menu,
                'repeat' => $repeat,
            ];
        }

        return null;
    }

    private function resolveCompositeMenuCandidate(string $candidate, Collection $nameIndex, Collection $aliasIndex): array
    {
        $tokens = array_values(array_filter(explode(' ', $candidate), fn (string $value) => $value !== ''));
        if (count($tokens) < 2) {
            return [];
        }

        $phraseMap = collect([])
            ->merge($nameIndex)
            ->merge($aliasIndex)
            ->filter(fn ($menu, $phrase) => is_string($phrase) && $phrase !== '')
            ->mapWithKeys(fn ($menu, $phrase) => [Str::lower($phrase) => $menu]);

        if ($phraseMap->isEmpty()) {
            return [];
        }

        $phrases = $phraseMap
            ->map(fn ($menu, $phrase) => [
                'phrase' => $phrase,
                'phrase_tokens' => explode(' ', $phrase),
                'menu' => $menu,
            ])
            ->values()
            ->sortByDesc(fn (array $item) => count($item['phrase_tokens']) * 1000 + strlen($item['phrase']))
            ->values();

        $cursor = 0;
        $matches = [];

        while ($cursor < count($tokens)) {
            $matched = null;

            foreach ($phrases as $phraseItem) {
                $length = count($phraseItem['phrase_tokens']);
                if ($cursor + $length > count($tokens)) {
                    continue;
                }

                $slice = array_slice($tokens, $cursor, $length);
                if (implode(' ', $slice) === $phraseItem['phrase']) {
                    $matched = $phraseItem;
                    break;
                }
            }

            if (! $matched) {
                // Skip unknown token so valid menu phrases can still be extracted.
                $cursor++;
                continue;
            }

            $matches[] = $matched['menu'];
            $cursor += count($matched['phrase_tokens']);
        }

        if (empty($matches)) {
            return [];
        }

        return collect($matches)
            ->groupBy(fn (Menu $menu) => $menu->id)
            ->map(function (Collection $group): array {
                return [
                    'menu' => $group->first(),
                    'qty' => $group->count(),
                ];
            })
            ->values()
            ->all();
    }

    private function findByFuzzyMatch(string $candidate, Collection $menus): ?Menu
    {
        $threshold = 80;
        $bestScore = 0;
        $bestMenu = null;

        foreach ($menus as $menu) {
            similar_text($candidate, Str::lower($menu->name), $nameScore);

            if ($nameScore > $bestScore) {
                $bestScore = $nameScore;
                $bestMenu = $menu;
            }

            foreach ($menu->aliases as $alias) {
                similar_text($candidate, Str::lower($alias->normalized_alias), $aliasScore);

                if ($aliasScore > $bestScore) {
                    $bestScore = $aliasScore;
                    $bestMenu = $menu;
                }
            }
        }

        return $bestScore >= $threshold ? $bestMenu : null;
    }

    private function readableItemList(array $items): string
    {
        return collect($items)
            ->map(fn (array $item) => $item['name'].($item['qty'] > 1 ? ' x'.$item['qty'] : ''))
            ->implode(', ');
    }
}
