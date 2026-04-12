<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GroqService
{
    private ?string $apiKey;

    private string $baseUrl;

    private string $validationModel;

    private string $sttModel;

    public function __construct()
    {
        $this->apiKey = config('services.groq.key');
        $this->baseUrl = rtrim((string) config('services.groq.base_url', 'https://api.groq.com/openai/v1'), '/');
        $this->validationModel = (string) config('services.groq.validation_model', 'openai/gpt-oss-20b');
        $this->sttModel = (string) config('services.groq.stt_model', 'whisper-large-v3-turbo');
    }

    public function isEnabled(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Ask Groq to extract structured order items.
     * Output is strictly sanitized before returning.
     *
     * @return array<int, array{name:string,qty:int}>
     */
    public function parseOrderItems(string $text): array
    {
        if (! $this->isEnabled() || trim($text) === '') {
            return [];
        }

        $prompt = <<<PROMPT
You are an extraction engine.
Extract menu items from Indonesian customer text.
Return JSON only, with this exact shape:
[{"name":"menu name","qty":1}]
Rules:
- Keep item names exactly aligned to spoken specific variants when present (example: "teh manis dingin" must stay "teh manis dingin", not "teh").
- qty must be integer >= 1.
- Capture additive intents such as "tambah", "satu lagi", "yang ... satu lagi" by increasing qty or adding new items.
- If no clear item, return []
Text: {$text}
PROMPT;

        $response = $this->chatRequest([
            'model' => $this->validationModel,
            'temperature' => 0,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => 'Output valid JSON only.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (! $response->successful()) {
            return [];
        }

        $content = data_get($response->json(), 'choices.0.message.content', '');

        return $this->sanitizeParsedItems($this->decodeItems($content));
    }

    /**
     * Ask Groq to choose the closest matching menu from the allowed names.
     */
    public function matchMenuCandidate(string $candidate, array $menuNames): ?string
    {
        if (! $this->isEnabled() || trim($candidate) === '' || empty($menuNames)) {
            return null;
        }

        $allowedNames = collect($menuNames)
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($allowedNames)) {
            return null;
        }

        $allowedJson = json_encode($allowedNames, JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
You are a semantic menu matcher for Indonesian food orders.
Choose the single best matching menu name from the allowed list for the candidate text.
Return JSON only in this exact shape:
{"matched_name":"menu name or null","confidence":0.0}
Rules:
- matched_name must be exactly one item from allowed_names or null.
- Prefer the closest semantic match, not literal substring match.
- If the candidate refers to a more general drink/food and one specific allowed menu is a better fit, choose that allowed menu.
- If no allowed menu is a reasonable match, return null.

candidate: {$candidate}
allowed_names: {$allowedJson}
PROMPT;

        $response = $this->chatRequest([
            'model' => $this->validationModel,
            'temperature' => 0,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => 'Output valid JSON only.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (! $response->successful()) {
            return null;
        }

        $content = data_get($response->json(), 'choices.0.message.content', '');
        $decoded = $this->decodeObject($content);

        $matchedName = mb_strtolower(trim((string) Arr::get($decoded, 'matched_name', '')));

        if ($matchedName === '' || $matchedName === 'null') {
            return null;
        }

        return collect($allowedNames)->first(fn (string $name) => $name === $matchedName) ?: null;
    }

    public function transcribeAudio(string $filePath, string $originalName = 'audio.wav'): string
    {
        if (! $this->isEnabled() || ! is_readable($filePath)) {
            return '';
        }

        $binary = file_get_contents($filePath);

        if ($binary === false) {
            return '';
        }

        $response = Http::timeout(30)
            ->withToken($this->apiKey)
            ->attach('file', $binary, $originalName)
            ->post($this->baseUrl.'/audio/transcriptions', [
                'model' => $this->sttModel,
                'language' => 'id',
                'prompt' => 'Percakapan dalam Bahasa Indonesia sehari-hari',
                'temperature' => 0,
            ]);

        if (! $response->successful()) {
            return '';
        }

        return (string) data_get($response->json(), 'text', '');
    }

    private function chatRequest(array $payload): Response
    {
        return Http::timeout(10)
            ->withToken($this->apiKey)
            ->acceptJson()
            ->post($this->baseUrl.'/chat/completions', $payload);
    }

    /**
     * @return array<int, mixed>
     */
    private function decodeItems(string $content): array
    {
        $decoded = $this->decodeJson($content);

        if (is_array($decoded) && isset($decoded[0])) {
            return $decoded;
        }

        if (is_array($decoded) && isset($decoded['items']) && is_array($decoded['items'])) {
            return $decoded['items'];
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeObject(string $content): array
    {
        $decoded = $this->decodeJson($content);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return mixed
     */
    private function decodeJson(string $content)
    {
        if ($content === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $trimmed = trim($content);
        $trimmed = preg_replace('/^```(?:json)?|```$/m', '', $trimmed) ?? $trimmed;

        return json_decode(trim($trimmed), true);
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, array{name:string,qty:int}>
     */
    private function sanitizeParsedItems(array $items): array
    {
        return collect($items)
            ->map(function ($item): ?array {
                $name = trim((string) Arr::get($item, 'name', ''));
                $qty = (int) Arr::get($item, 'qty', 1);

                if ($name === '') {
                    return null;
                }

                return [
                    'name' => mb_strtolower($name),
                    'qty' => max(1, $qty),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
