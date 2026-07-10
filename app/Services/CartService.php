<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CartService
{
    public function getOrCreateCart(?int $userId = null, ?string $sessionId = null, mixed $expiresAt = null): Cart
    {
        if (($userId === null && $sessionId === null) || ($userId !== null && $sessionId !== null)) {
            throw new RuntimeException('Cart must belong to either a user or a session.');
        }

        $lookup = $userId !== null ? ['user_id' => $userId] : ['session_id' => $sessionId];

        return Cart::query()->firstOrCreate($lookup, [
            'expires_at' => $expiresAt,
        ]);
    }

    public function addItem(
        ?int $userId,
        ?string $sessionId,
        int $productVariantId,
        int $quantity
    ): CartItem {
        $this->assertQuantity($quantity);

        return DB::transaction(function () use ($userId, $sessionId, $productVariantId, $quantity) {
            $cart = $this->getOrCreateCart($userId, $sessionId);
            $variant = ProductVariant::query()->with('product')->whereKey($productVariantId)->firstOrFail();
            $unitPriceSnapshot = $this->resolveUnitPrice($variant);

            $item = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_variant_id', $productVariantId)
                ->lockForUpdate()
                ->first();

            if ($item) {
                $newQuantity = $item->quantity + $quantity;
                $this->assertQuantity($newQuantity);

                $item->update([
                    'quantity' => $newQuantity,
                    'unit_price_snapshot' => $unitPriceSnapshot,
                ]);

                return $item->refresh();
            }

            return CartItem::query()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $productVariantId,
                'quantity' => $quantity,
                'unit_price_snapshot' => $unitPriceSnapshot,
            ]);
        });
    }

    public function updateItemQuantity(CartItem|int $cartItem, int $quantity): CartItem
    {
        $this->assertQuantity($quantity);

        return DB::transaction(function () use ($cartItem, $quantity) {
            $item = $this->lockCartItem($cartItem);

            $item->update(['quantity' => $quantity]);

            return $item->refresh();
        });
    }

    public function removeItem(CartItem|int $cartItem): bool
    {
        return (bool) $this->resolveCartItem($cartItem)->delete();
    }

    public function clearCart(Cart|int $cart): int
    {
        $cart = $this->resolveCart($cart);

        return $cart->items()->delete();
    }

    public function calculateTotals(Cart|int $cart): array
    {
        $cart = $this->resolveCart($cart);
        $items = $cart->items()->get();

        $subtotal = $items->reduce(function (float $carry, CartItem $item) {
            return $carry + ((float) $item->unit_price_snapshot * $item->quantity);
        }, 0.0);

        return [
            'items_count' => $items->count(),
            'quantity_total' => $items->sum('quantity'),
            'subtotal' => round($subtotal, 2),
        ];
    }

    /**
     * Re-snapshot each line item from current variant/product pricing before checkout.
     */
    public function refreshUnitPrices(Cart|int $cart): Cart
    {
        $cart = $this->resolveCart($cart);
        $cart->loadMissing(['items.productVariant.product']);

        foreach ($cart->items as $item) {
            $variant = $item->productVariant;

            if (! $variant || ! $variant->product) {
                continue;
            }

            $item->update([
                'unit_price_snapshot' => $this->resolveUnitPrice($variant),
            ]);
        }

        return $cart->refresh()->loadMissing(['items.productVariant.product', 'items.productVariant.color', 'items.productVariant.size']);
    }

    private function resolveUnitPrice(ProductVariant $variant): float
    {
        if ($variant->price_override !== null) {
            return (float) $variant->price_override;
        }

        return (float) $variant->product->base_price;
    }

    private function assertQuantity(int $quantity): void
    {
        if ($quantity < 1 || $quantity > 10) {
            throw new RuntimeException('Cart item quantity must be between 1 and 10.');
        }
    }

    private function resolveCart(Cart|int $cart): Cart
    {
        if ($cart instanceof Cart) {
            return $cart;
        }

        return Cart::query()->findOrFail($cart);
    }

    private function resolveCartItem(CartItem|int $cartItem): CartItem
    {
        if ($cartItem instanceof CartItem) {
            return $cartItem;
        }

        return CartItem::query()->findOrFail($cartItem);
    }

    private function lockCartItem(CartItem|int $cartItem): CartItem
    {
        $cartItemId = $cartItem instanceof CartItem ? $cartItem->id : $cartItem;

        return CartItem::query()
            ->whereKey($cartItemId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
