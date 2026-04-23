<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOrderToLayer2 implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(OrderCreated $event): void
    {
        $order = Order::query()
            ->with('items')
            ->find($event->orderId);

        if (!$order) {
            Log::warning("Order {$event->orderId} not found for Layer 2 sync");
            return;
        }

        $baseUrl = rtrim((string) config('services.layer2.base_url', 'http://127.0.0.1:8002'), '/');
        $path = '/'.ltrim((string) config('services.layer2.incoming_order_path', '/api/orders/incoming'), '/');
        $endpoint = $baseUrl.$path;

        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->asJson()
                ->post($endpoint, [
                    'order_id'      => $order->id,
                    'order_code'    => $order->order_code,
                    'customer_name' => $order->customer_name,
                    'gender'        => $order->gender,
                    'status'        => 'waiting',
                    'items'         => $order->items->map(fn ($item) => [
                        'name' => $item->item_name,
                        'qty' => $item->qty,
                        'note' => $item->note,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->subtotal,
                    ])->toArray(),
                    'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at->toIso8601String(),
                ]);

            if ($response->failed()) {
                Log::error('Layer 2 sync failed', [
                    'order_id' => $order->id,
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new \RuntimeException('Layer 2 API error: '.$response->status());
            }

            Log::info('Order sent to Layer 2 successfully', [
                'order_id' => $order->id,
                'endpoint' => $endpoint,
            ]);
        } catch (Throwable $e) {
            Log::error('Error sending order to Layer 2', [
                'order_id' => $order->id,
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
