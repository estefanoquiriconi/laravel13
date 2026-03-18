<?php

namespace App\Http\Controllers;

use App\Actions\Store\CreateOrder;
use App\Actions\Store\DispatchOrderProcessing;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

#[Middleware('auth:sanctum')]
class OrderController extends Controller
{
    /**
     * Display a listing of the user's orders.
     */
    #[Authorize('viewAny', Order::class)]
    public function index(): AnonymousResourceCollection
    {
        return OrderResource::collection(
            auth()->user()->orders()
                ->with('items.product')
                ->latest()
                ->get()
        );
    }

    /**
     * Store a newly created order.
     */
    public function store(
        StoreOrderRequest $request,
        CreateOrder $createOrder,
        DispatchOrderProcessing $dispatchProcessing,
    ): JsonResponse {
        $order = DB::transaction(
            fn () => $createOrder->create(auth()->user(), $request->validated('items'))
        );

        $dispatchProcessing->dispatch($order);

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }
}
