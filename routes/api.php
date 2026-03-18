<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('store')->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->name('api.store.products.index');
    Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('api.store.products.show');

    Route::get('/orders', [OrderController::class, 'index'])->name('api.store.orders.index');
    Route::post('/orders', [OrderController::class, 'store'])->name('api.store.orders.store');
});
