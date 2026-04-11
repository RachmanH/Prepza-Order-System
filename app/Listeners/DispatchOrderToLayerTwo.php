<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class DispatchOrderToLayerTwo implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        // Placeholder dispatch to Layer 2 bus/service. Keep this async and non-blocking.
        Log::info('Order dispatched to Layer 2 pipeline', [
            'order_id' => $event->orderId,
            'queue_number' => $event->queueNumber,
            'items' => $event->items,
        ]);
    }
}
