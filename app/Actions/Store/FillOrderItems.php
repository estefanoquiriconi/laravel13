<?php

namespace App\Actions\Store;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class FillOrderItems
{
    /**
     * Resolve products from the given items.
     *
     * @param  array<int, array{product_id: string, quantity: int}>  $items
     * @return Collection<int, Product>
     */
    public function resolve(array $items): Collection
    {
        return Product::query()
            ->whereIn('id', collect($items)->pluck('product_id'))
            ->get()
            ->keyBy('id');
    }

    /**
     * Calculate the order total.
     *
     * @param  Collection<int, Product>  $products
     * @param  array<int, array{product_id: string, quantity: int}>  $items
     */
    public function calculateTotal(Collection $products, array $items): float
    {
        return collect($items)->sum(
            fn (array $item) => $products[$item['product_id']]->price * $item['quantity']
        );
    }

    /**
     * Create order items and decrement product stock.
     *
     * @param  Collection<int, Product>  $products
     * @param  array<int, array{product_id: string, quantity: int}>  $items
     */
    public function fill(Order $order, Collection $products, array $items): void
    {
        foreach ($items as $item) {
            $product = $products[$item['product_id']];

            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
            ]);

            $product->decrement('stock', $item['quantity']);
        }
    }
}
