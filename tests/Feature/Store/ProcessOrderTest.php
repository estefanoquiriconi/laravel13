<?php

use App\Enums\OrderStatus;
use App\Jobs\ProcessOrder;
use App\Models\Order;

test('the job processes an order to completed', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    (new ProcessOrder($order))->handle();

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Completed);
});
