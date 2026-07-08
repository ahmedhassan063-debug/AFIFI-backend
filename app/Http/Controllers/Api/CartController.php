<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Http\Resources\CartItemResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly CartService $cartService)
    {
    }

    public function show(): CartResource
    {
        $cart = $this->currentCart();
        $this->authorize('view', $cart);

        return new CartResource($cart->load('items.productVariant'));
    }

    public function addItem(AddCartItemRequest $request): CartItemResource
    {
        $data = $request->validated();
        $item = $this->cartService->addItem(
            auth()->user()->id,
            null,
            $data['product_variant_id'],
            $data['quantity']
        );

        return new CartItemResource($item->load('productVariant'));
    }

    public function updateItem(UpdateCartItemRequest $request, CartItem $cartItem): CartItemResource
    {
        $this->ensureCartItemBelongsToUser($cartItem);
        $this->authorize('update', $cartItem);

        $item = $this->cartService->updateItemQuantity($cartItem, $request->validated()['quantity']);

        return new CartItemResource($item->load('productVariant'));
    }

    public function removeItem(CartItem $cartItem): JsonResponse
    {
        $this->ensureCartItemBelongsToUser($cartItem);
        $this->authorize('delete', $cartItem);
        $this->cartService->removeItem($cartItem);

        return response()->json([
            'message' => 'Cart item removed successfully.',
        ]);
    }

    public function clear(): JsonResponse
    {
        $cart = $this->currentCart();
        $this->authorize('clear', $cart);

        $this->cartService->clearCart($cart);

        return response()->json([
            'message' => 'Cart cleared successfully.',
        ]);
    }

    private function currentCart(): Cart
    {
        return $this->cartService->getOrCreateCart(auth()->user()->id);
    }

    private function ensureCartItemBelongsToUser(CartItem $cartItem): void
    {
        abort_unless($cartItem->cart?->user_id === auth()->user()->id, 404);
    }
}
