<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GroqService
{
    private ?string $apiKey;

    private string $baseUrl;

    private string $chatModel;

    private string $sttModel;

    public function __construct()
    {
        $this->apiKey = config('services.groq.key');
        $this->baseUrl = rtrim((string) config('services.groq.base_url', 'https://api.groq.com/openai/v1'), '/');
        $this->chatModel = (string) config('services.groq.model', 'llama-3.1-8b-instant');
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
- Keep item names short and normalized.
- qty must be integer >= 1.
- If no clear item, return []
Text: {$text}
PROMPT;

        $response = $this->chatRequest([
            'model' => $this->chatModel,
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
        if ($content === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        if (is_array($decoded) && isset($decoded[0])) {
            return $decoded;
        }

        if (is_array($decoded) && isset($decoded['items']) && is_array($decoded['items'])) {
            return $decoded['items'];
        }

        $trimmed = trim($content);
        $trimmed = preg_replace('/^```(?:json)?|```$/m', '', $trimmed) ?? $trimmed;
        $decoded = json_decode(trim($trimmed), true);

        if (is_array($decoded) && isset($decoded[0])) {
            return $decoded;
        }

        if (is_array($decoded) && isset($decoded['items']) && is_array($decoded['items'])) {
            return $decoded['items'];
        }

        return [];
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
