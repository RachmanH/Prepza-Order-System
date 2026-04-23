<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\QueueTrend;
use App\Services\GroqService;
use App\Services\Layer2NotifierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CashierOrderController extends Controller
{
    public function __construct(
        private readonly GroqService $groqService,
        private readonly Layer2NotifierService $layer2,
    ) {}

    public function board(): JsonResponse
    {
        $queueRows = Order::query()
            ->join('order_queues as oq', 'oq.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['waiting', 'processing', 'done'])
            ->whereDate('orders.created_at', today())
            ->orderByRaw("CASE WHEN orders.status = 'processing' THEN 0 WHEN orders.status = 'waiting' THEN 1 ELSE 2 END")
            ->orderBy('oq.queue_number')
            ->get([
                'orders.id',
                'orders.order_code',
                'orders.status',
                'orders.external_status',
                'orders.external_updated_at',
                'orders.customer_name',
                'oq.queue_number',
                'oq.called_at',
                'oq.done_at',
            ]);

        $displayQueueMap = [];
        foreach ($queueRows->sortBy('queue_number')->values() as $index => $row) {
            $displayQueueMap[(int) $row->queue_number] = $index + 1;
        }

        $effectiveRows = $queueRows->map(function ($row) use ($displayQueueMap) {
            $effectiveStatus = $row->external_status === 'done' ? 'done' : $row->status;
            $row->effective_status = $effectiveStatus;
            $row->display_queue_number = $displayQueueMap[(int) $row->queue_number] ?? (int) $row->queue_number;

            return $row;
        });

        $current = $effectiveRows->first(fn ($row) => $row->effective_status === 'processing')
            ?? $effectiveRows->first(fn ($row) => $row->effective_status === 'waiting');

        $upcoming = $effectiveRows
            ->filter(fn ($row) => in_array($row->effective_status, ['waiting', 'processing'], true))
            ->reject(fn ($row) => $current && (int) $row->id === (int) $current->id)
            ->take(6)
            ->values()
            ->map(fn ($row): array => [
                'order_id' => $row->id,
                'queue_number' => $row->queue_number,
                'display_queue_number' => $row->display_queue_number,
                'order_code' => $row->order_code,
                'customer_name' => $row->customer_name,
                'status' => $row->effective_status,
            ])
            ->all();

        $recentDone = $effectiveRows
            ->filter(fn ($row) => $row->effective_status === 'done')
            ->sortByDesc(fn ($row) => $row->done_at ?? $row->external_updated_at)
            ->take(8)
            ->values()
            ->map(fn ($row): array => [
                'order_id' => $row->id,
                'queue_number' => $row->queue_number,
                'display_queue_number' => $row->display_queue_number,
                'customer_name' => $row->customer_name,
                'done_at' => optional($row->done_at)->toIso8601String(),
                'external_updated_at' => optional($row->external_updated_at)->toIso8601String(),
                'announce_key' => sprintf(
                    '%s:%s',
                    (string) $row->id,
                    optional($row->done_at ?? $row->external_updated_at)->toIso8601String() ?? 'no-ts'
                ),
            ])
            ->all();

        $trend = $this->activeTrend();
        $trendsByGender = $this->activeTrendsByGender();

        return response()->json([
            'data' => [
                'current' => $current ? [
                    'order_id' => $current->id,
                    'queue_number' => $current->queue_number,
                    'display_queue_number' => $current->display_queue_number,
                    'order_code' => $current->order_code,
                    'customer_name' => $current->customer_name,
                    'status' => $current->effective_status,
                ] : null,
                'upcoming' => $upcoming,
                'recent_done' => $recentDone,
                'trend'       => $trend,
                'trends'      => is_array($trend) ? $trend : ($trend ? [$trend] : []),
                'trends_by_gender' => $trendsByGender,
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    public function updateTrend(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'title'            => ['required', 'string', 'max:120'],
            'image_url'        => ['required', 'url', 'max:2048'],
            'caption'          => ['nullable', 'string', 'max:300'],
            'score'            => ['nullable', 'integer', 'min:0', 'max:100'],
            'gender_target'    => ['nullable', 'in:male,female,all'],
            'source_timestamp' => ['nullable', 'date'],
            'expires_at'       => ['nullable', 'date'],   // allow past dates — we'll handle below
            'is_active'        => ['nullable', 'boolean'],
        ]);

        // Auto-detect gender_target from title if not explicitly sent
        $genderTarget = $payload['gender_target'] ?? null;
        if (! $genderTarget) {
            $titleLower = mb_strtolower($payload['title']);
            if (preg_match('/\b(laki.laki|pria|male|cowok|cowo)\b/', $titleLower)) {
                $genderTarget = 'male';
            } elseif (preg_match('/\b(perempuan|wanita|female|cewek|cewe)\b/', $titleLower)) {
                $genderTarget = 'female';
            } else {
                $genderTarget = 'all';
            }
        }

        // If expires_at is already in the past, treat as no expiry (Layer 2 may send stale timestamps)
        $expiresAt = isset($payload['expires_at']) ? now()->parse($payload['expires_at']) : null;
        if ($expiresAt && $expiresAt->isPast()) {
            $expiresAt = null;
        }

        // Upsert by title — update existing active trend with same title instead of creating duplicates
        $trend = QueueTrend::query()->updateOrCreate(
            ['title' => $payload['title']],
            [
                'image_url'        => $payload['image_url'],
                'caption'          => $payload['caption'] ?? null,
                'score'            => $payload['score'] ?? null,
                'gender_target'    => $genderTarget,
                'source_timestamp' => isset($payload['source_timestamp']) ? now()->parse($payload['source_timestamp']) : now(),
                'expires_at'       => $expiresAt,
                'is_active'        => (bool) ($payload['is_active'] ?? true),
                'source_payload'   => $payload,
            ]
        );

        return response()->json([
            'status'  => 'ok',
            'message' => 'Tren makanan berhasil diperbarui.',
            'data'    => $this->serializeTrend($trend),
        ]);
    }

    private function activeTrend(): ?array
    {
        // Return up to 5 active trends for carousel — ordered by gender_target priority then recency
        $trends = $this->activeTrendCollection(12)
            ->sortBy(fn (QueueTrend $trend) => match ($trend->gender_target) {
                'female' => 0,
                'male' => 1,
                default => 2,
            })
            ->take(5)
            ->values();

        if ($trends->isEmpty()) {
            return null;
        }

        // Return array of trends for carousel; keep single-item backward compat via first element
        return $trends->map(fn (QueueTrend $t): array => $this->serializeTrend($t))->values()->all();
    }

    /**
     * @return array{all:array<int,array<string,mixed>>,male:array<int,array<string,mixed>>,female:array<int,array<string,mixed>>}
     */
    private function activeTrendsByGender(): array
    {
        $trends = $this->activeTrendCollection(20)
            ->map(fn (QueueTrend $trend): array => $this->serializeTrend($trend))
            ->values();

        $all = $trends
            ->filter(fn (array $trend): bool => ($trend['gender_target'] ?? 'all') === 'all')
            ->take(5)
            ->values()
            ->all();

        $male = $trends
            ->filter(fn (array $trend): bool => in_array($trend['gender_target'] ?? 'all', ['male', 'all'], true))
            ->take(5)
            ->values()
            ->all();

        $female = $trends
            ->filter(fn (array $trend): bool => in_array($trend['gender_target'] ?? 'all', ['female', 'all'], true))
            ->take(5)
            ->values()
            ->all();

        return [
            'all' => $all,
            'male' => $male,
            'female' => $female,
        ];
    }

    private function activeTrendCollection(int $limit = 12): Collection
    {
        return QueueTrend::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('source_timestamp')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    private function serializeTrend(QueueTrend $trend): array
    {
        return [
            'id'               => $trend->id,
            'title'            => $trend->title,
            'image_url'        => $trend->image_url,
            'caption'          => $trend->caption,
            'score'            => $trend->score,
            'gender_target'    => $trend->gender_target ?? 'all',
            'source_timestamp' => $trend->source_timestamp?->toIso8601String(),
            'expires_at'       => $trend->expires_at?->toIso8601String(),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $orders = Order::query()
            ->with(['items:id,order_id,item_name,note,qty,subtotal', 'queue:queue_number,order_id,status'])
            ->leftJoin('order_queues as oq', 'oq.order_id', '=', 'orders.id')
            ->select('orders.id', 'orders.order_code', 'orders.customer_name', 'orders.gender', 'orders.status', 'orders.external_status', 'orders.external_note', 'orders.external_updated_at', 'orders.total_amount', 'orders.created_at')
            ->when(
                $status,
                fn ($query) => $query->whereIn('orders.status', explode(',', (string) $status)),
                fn ($query) => $query->whereIn('orders.status', ['queued', 'waiting', 'processing'])
            )
            ->orderByRaw("CASE WHEN oq.status IN ('waiting','processing') THEN 0 ELSE 1 END")
            ->orderBy('oq.queue_number')
            ->orderBy('orders.id')
            ->get();

        return response()->json([
            'data' => $orders,
        ]);
    }

    public function confirm(Order $order): JsonResponse
    {
        return $this->startProcessing($order);
    }

    public function startProcessing(Order $order): JsonResponse
    {
        return response()->json([
            'status' => 'forbidden',
            'message' => 'Aksi mulai proses hanya dapat dikendalikan dari Layer 2.',
        ], 403);
    }

    public function finish(Order $order): JsonResponse
    {
        return response()->json([
            'status' => 'forbidden',
            'message' => 'Aksi selesaikan hanya dapat dikendalikan dari Layer 2.',
        ], 403);
    }

    public function cancel(Order $order): JsonResponse
    {
        if ($order->status === 'done') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Order yang sudah selesai tidak bisa dibatalkan.',
            ], 422);
        }

        DB::transaction(function () use ($order): void {
            $order->update(['status' => 'cancelled']);

            if ($order->queue) {
                $order->queue->update([
                    'status'  => 'done',
                    'done_at' => now(),
                ]);
            }
        });

        // Notify Layer 2 about cancellation
        $this->layer2->notifyStatusChange($order, 'cancelled', 'Order dibatalkan dari Layer 1.');

        return response()->json([
            'status'  => 'ok',
            'message' => 'Order dibatalkan.',
        ]);
    }

    public function simulateExternalUpdate(Request $request, Order $order): JsonResponse
    {
        $payload = $request->validate([
            'external_status' => ['nullable', 'in:waiting,processing,done'],
            'external_note' => ['nullable', 'string', 'max:500'],
            'queue_status' => ['nullable', 'in:waiting,processing,done,cancelled'],
        ]);

        if (! array_key_exists('queue_status', $payload) && array_key_exists('external_status', $payload) && $payload['external_status']) {
            $payload['queue_status'] = $payload['external_status'];
        }

        DB::transaction(function () use ($order, $payload): void {
            if (($payload['queue_status'] ?? null) === 'processing') {
                $otherProcessingOrderIds = Order::query()
                    ->where('id', '!=', $order->id)
                    ->whereDate('created_at', $order->created_at->toDateString())
                    ->where('status', 'processing')
                    ->pluck('id');

                if ($otherProcessingOrderIds->isNotEmpty()) {
                    Order::query()
                        ->whereIn('id', $otherProcessingOrderIds)
                        ->update(['status' => 'waiting']);

                    DB::table('order_queues')
                        ->whereIn('order_id', $otherProcessingOrderIds)
                        ->where('status', 'processing')
                        ->update(['status' => 'waiting']);
                }
            }

            $orderData = [
                'external_updated_at' => now(),
            ];

            if (array_key_exists('external_status', $payload) && $payload['external_status'] !== null) {
                $orderData['external_status'] = $payload['external_status'];
            }

            if (array_key_exists('external_note', $payload)) {
                $orderData['external_note'] = $payload['external_note'];
            }

            if (array_key_exists('queue_status', $payload) && $payload['queue_status']) {
                $orderData['status'] = $payload['queue_status'];
            }

            $order->update($orderData);

            if ($order->queue && array_key_exists('queue_status', $payload) && $payload['queue_status']) {
                $queueStatus = $payload['queue_status'] === 'cancelled' ? 'done' : $payload['queue_status'];
                $queueUpdate = ['status' => $queueStatus];

                if ($queueStatus === 'processing') {
                    $queueUpdate['called_at'] = $order->queue->called_at ?? now();
                }

                if ($queueStatus === 'done') {
                    $queueUpdate['done_at'] = now();
                }

                $order->queue->update($queueUpdate);
            }
        });

        $order->load(['items:id,order_id,item_name,note,qty,subtotal', 'queue:queue_number,order_id,status']);

        // Notify Layer 2 about the status change (processing / done / waiting / cancelled)
        $newStatus = $payload['queue_status'] ?? $payload['external_status'] ?? null;
        if ($newStatus) {
            $this->layer2->notifyStatusChange($order, $newStatus, $payload['external_note'] ?? null);
        }

        return response()->json([
            'status'     => 'ok',
            'message'    => 'Simulasi input eksternal berhasil diproses.',
            'order'      => $order,
            'updated_by' => Auth::id(),
        ]);
    }

    public function appendVoice(Request $request, Order $order): JsonResponse
    {
        if (in_array($order->status, ['cancelled', 'done'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order ini tidak bisa ditambah lagi.',
            ], 422);
        }

        $payload = $request->validate([
            'raw_text' => ['required', 'string', 'max:500'],
        ]);

        $rawText = Str::of($payload['raw_text'])->squish()->toString();
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

        $validated = $this->validateItems($parsed['items']);

        if ($validated['status'] === 'invalid') {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Item tambahan tidak dikenali, silakan ulangi.',
            ], 422);
        }

        if ($validated['status'] === 'partial') {
            return response()->json([
                'status' => 'partial',
                'message' => 'Sebagian item tidak tersedia: '.implode(', ', $validated['invalid']),
                'valid' => $validated['valid_names'],
                'invalid' => $validated['invalid'],
            ], 422);
        }

        DB::transaction(function () use ($order, $rawText, $validated): void {
            foreach ($validated['valid_items'] as $item) {
                $unitPrice = (float) $item['menu']->price;
                $subtotal = $unitPrice * $item['qty'];

                $order->items()->create([
                    'menu_id' => $item['menu']->id,
                    'item_name' => $item['menu']->name,
                    'note' => null,
                    'qty' => $item['qty'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update([
                'raw_text' => trim($order->raw_text.' | + '.$rawText),
                'total_amount' => (float) $order->items()->sum('subtotal'),
            ]);
        });

        $order->load(['items:id,order_id,item_name,note,qty,subtotal', 'queue:queue_number,order_id,status']);

        return response()->json([
            'status' => 'ok',
            'message' => 'Item tambahan berhasil ditambahkan.',
            'order' => $order,
        ]);
    }

    public function updateItem(Request $request, Order $order, OrderItem $item): JsonResponse
    {
        if ($item->order_id !== $order->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Item tidak terdaftar pada order ini.',
            ], 404);
        }

        if (in_array($order->status, ['cancelled', 'done'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order ini tidak bisa diubah lagi.',
            ], 422);
        }

        $payload = $request->validate([
            'qty' => ['nullable', 'integer', 'min:1', 'max:99', 'required_without:note'],
            'note' => ['nullable', 'string', 'max:255', 'required_without:qty'],
        ]);

        DB::transaction(function () use ($order, $item, $payload): void {
            $itemUpdates = [];

            if (array_key_exists('qty', $payload) && $payload['qty'] !== null) {
                $qty = (int) $payload['qty'];
                $unitPrice = (float) ($item->unit_price ?? 0);

                if ($unitPrice <= 0 && (int) $item->qty > 0) {
                    $unitPrice = (float) $item->subtotal / (int) $item->qty;
                }

                $itemUpdates['qty'] = $qty;
                $itemUpdates['unit_price'] = $unitPrice;
                $itemUpdates['subtotal'] = $unitPrice * $qty;
            }

            if (array_key_exists('note', $payload)) {
                $note = trim((string) ($payload['note'] ?? ''));
                $itemUpdates['note'] = $note !== '' ? $note : null;
            }

            if (! empty($itemUpdates)) {
                $item->update($itemUpdates);
            }

            $order->update([
                'total_amount' => (float) $order->items()->sum('subtotal'),
            ]);
        });

        $order->load(['items:id,order_id,item_name,note,qty,subtotal', 'queue:queue_number,order_id,status']);

        return response()->json([
            'status' => 'ok',
            'message' => array_key_exists('note', $payload) && ! array_key_exists('qty', $payload)
                ? 'Keterangan item berhasil diperbarui.'
                : 'Item order berhasil diperbarui.',
            'order' => $order,
        ]);
    }

    public function removeItem(Order $order, OrderItem $item): JsonResponse
    {
        if ($item->order_id !== $order->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Item tidak terdaftar pada order ini.',
            ], 404);
        }

        if (in_array($order->status, ['cancelled', 'done'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order ini tidak bisa diubah lagi.',
            ], 422);
        }

        DB::transaction(function () use ($order, $item): void {
            $item->delete();

            $order->update([
                'total_amount' => (float) $order->items()->sum('subtotal'),
            ]);
        });

        $order->load(['items:id,order_id,item_name,note,qty,subtotal', 'queue:queue_number,order_id,status']);

        return response()->json([
            'status' => 'ok',
            'message' => 'Item berhasil dihapus dari order.',
            'order' => $order,
        ]);
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
}
