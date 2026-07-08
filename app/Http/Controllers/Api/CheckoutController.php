<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService,
    ) {
    }

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $data = $request->validated();

        $cart = $this->cartService->getOrCreateCart(auth()->user()->id);
        $order = $this->checkoutService->checkout($cart, $data);

        return (new OrderResource($order->load([
            'addresses',
            'items',
            'payments',
            'couponRedemption',
        ])))
            ->response()
            ->setStatusCode(201);
    }
}
