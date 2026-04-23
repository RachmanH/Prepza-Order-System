<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class Layer2NotifierService
{
    /**
     * Notify Layer 2 about an order status change.
     *
     * Statuses sent: waiting | processing | done | cancelled
     */
    public function notifyStatusChange(Order $order, string $status, ?string $note = null): void
    {
        $baseUrl  = rtrim((string) config('services.layer2.base_url', 'http://127.0.0.1:8002'), '/');
        $endpoint = $baseUrl . '/api/orders/status-update';

        $payload = [
            'order_id'   => $order->id,
            'order_code' => $order->order_code,
            'status'     => $status,
            'note'       => $note,
            'updated_at' => now()->toIso8601String(),
        ];

        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->asJson()
                ->post($endpoint, $payload);

            if ($response->failed()) {
                Log::warning('Layer 2 status notify failed', [
                    'order_id' => $order->id,
                    'status'   => $status,
                    'http'     => $response->status(),
                    'body'     => $response->body(),
                ]);
            } else {
                Log::info('Layer 2 status notified', [
                    'order_id' => $order->id,
                    'status'   => $status,
                ]);
            }
        } catch (Throwable $e) {
            // Fire-and-forget — log but never throw so the main flow is not blocked
            Log::warning('Layer 2 status notify exception', [
                'order_id' => $order->id,
                'status'   => $status,
                'message'  => $e->getMessage(),
            ]);
        }
    }
}
