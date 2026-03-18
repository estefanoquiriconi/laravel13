<?php

use App\Jobs\ProcessOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

test('guests cannot view orders', function () {
    $response = $this->getJson('/api/store/orders');

    $response->assertUnauthorized();
});

test('authenticated users can view their orders', function () {
    $user = User::factory()->create();
    Order::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/store/orders');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

test('an order can be placed with items', function () {
    Queue::fake();

    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 10, 'price' => 25.00]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/store/orders', [
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.total', '50.00');

    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'total' => 50.00,
    ]);

    $this->assertDatabaseHas('order_items', [
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 25.00,
    ]);

    expect($product->fresh()->stock)->toBe(8);

    Queue::assertPushed(ProcessOrder::class);
});

test('an order requires at least one item', function () {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/store/orders', [
        'items' => [],
    ]);

    $response->assertJsonValidationErrors('items');
});

test('an order validates product existence', function () {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/store/orders', [
        'items' => [
            ['product_id' => 'nonexistent', 'quantity' => 1],
        ],
    ]);

    $response->assertJsonValidationErrors('items.0.product_id');
});
