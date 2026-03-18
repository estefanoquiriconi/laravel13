<?php

namespace App\Actions\Store;

use App\Models\Order;
use App\Models\User;

class CreateOrder
{
    /**
     * Create a new order for the given user.
     *
     * @param  array<int, array{product_id: string, quantity: int}>  $items
     */
    public function create(User $user, array $items): Order
    {
        $fillItems = app(FillOrderItems::class);

        $products = $fillItems->resolve($items);

        $total = $fillItems->calculateTotal($products, $items);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'total' => $total,
            'status' => 'pending',
        ]);

        $fillItems->fill($order, $products, $items);

        return $order->load('items.product');
    }
}
