<?php

namespace App\Actions\Store;

use App\Jobs\ProcessOrder;
use App\Models\Order;

class DispatchOrderProcessing
{
    /**
     * Dispatch the order processing job.
     */
    public function dispatch(Order $order): void
    {
        ProcessOrder::dispatch($order);
    }
}
