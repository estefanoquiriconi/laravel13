<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth:sanctum', except: ['index', 'show'])]
class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection(Product::query()->orderBy('name')->get());
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): ProductResource
    {
        return new ProductResource($product);
    }
}
