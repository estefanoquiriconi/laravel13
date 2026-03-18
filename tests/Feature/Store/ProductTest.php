<?php

use App\Models\Product;

test('anyone can list products via API', function () {
    Product::factory(3)->create();

    $response = $this->getJson('/api/store/products');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('anyone can view a single product via API', function () {
    $product = Product::factory()->create();

    $response = $this->getJson("/api/store/products/{$product->slug}");

    $response->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.name', $product->name);
});

test('product response has the correct structure', function () {
    Product::factory()->create();

    $response = $this->getJson('/api/store/products');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                ['id', 'name', 'description', 'price', 'stock', 'slug'],
            ],
        ]);
});
