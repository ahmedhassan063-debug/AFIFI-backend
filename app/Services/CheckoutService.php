<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Order;
use App\Models\ShippingRate;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CouponService $couponService,
        private readonly InventoryService $inventoryService,
        private readonly OrderService $orderService,
        private readonly PaymentService $paymentService,
    ) {
    }

    public function checkout(Cart|int $cart, array $data): Order
    {
        return DB::transaction(function () use ($cart, $data) {
            $cart = $this->resolveCart($cart);
            $cart->loadMissing(['items.productVariant.product', 'items.productVariant.color', 'items.productVariant.size']);
            $this->validateCart($cart);
            $this->validateCheckoutData($data);

            $currency = $this->resolveCurrency($data['currency_id'] ?? null, $data['currency_code'] ?? null);
            $shippingFee = $this->resolveShippingFee($data);
            $totals = $this->cartService->calculateTotals($cart);
            $discountTotal = 0.0;
            $coupon = null;

            if (! empty($data['coupon_code']) || ! empty($data['coupon_id'])) {
                $coupon = $this->resolveCoupon($data['coupon_id'] ?? $data['coupon_code']);
                $discountTotal = $this->couponService->calculateDiscount($coupon, $totals['subtotal']);
            }

            $grandTotal = round(max(0, $totals['subtotal'] + $shippingFee - $discountTotal), 2);
            $reservations = [];
            $sortedItems = $cart->items->sortBy('product_variant_id')->values();

            foreach ($sortedItems as $item) {
                $reservations[] = $this->inventoryService->holdReservation(
                    $item->product_variant_id,
                    $item->quantity,
                    $data['reservation_expires_at'] ?? now()->addMinutes(15),
                    $cart->user_id,
                    $cart->session_id,
                    'Checkout stock hold.'
                );
            }

            $order = $this->orderService->createOrderFromCartSnapshot($cart, [
                'user_id' => $cart->user_id,
                'guest_email' => $data['guest_email'] ?? null,
                'guest_phone' => $data['guest_phone'] ?? null,
                'currency_id' => $currency->id,
                'currency_code' => $currency->code,
                'exchange_rate' => $currency->exchange_rate,
                'status' => $data['status'] ?? 'pending_confirmation',
                'payment_status' => 'unpaid',
                'payment_method' => $data['payment_method'],
                'subtotal' => $totals['subtotal'],
                'shipping_fee' => $shippingFee,
                'discount_total' => $discountTotal,
                'grand_total' => $grandTotal,
                'coupon_id' => $coupon?->id,
                'customer_notes' => $data['customer_notes'] ?? null,
                'admin_notes' => $data['admin_notes'] ?? null,
            ], $data['address']);

            foreach ($reservations as $reservation) {
                $this->inventoryService->confirmReservation($reservation, 'Checkout confirmed.');
            }

            if ($coupon !== null) {
                $this->couponService->recordRedemption($coupon, $order->id, $discountTotal, $cart->user_id);
            }

            $this->paymentService->createPaymentRecord($order, [
                'provider' => $data['payment_provider'] ?? $data['payment_method'],
                'amount' => $grandTotal,
                'currency' => $currency->code,
                'status' => 'pending',
                'metadata' => $data['payment_metadata'] ?? null,
            ]);

            $this->cartService->clearCart($cart);

            return $order->refresh();
        });
    }

    private function validateCart(Cart $cart): void
    {
        if ($cart->items->isEmpty()) {
            throw new RuntimeException('Cart is empty.');
        }

        foreach ($cart->items as $item) {
            if (! $item->productVariant || ! $item->productVariant->is_active) {
                throw new RuntimeException('Cart contains an unavailable product variant.');
            }

            if ($this->inventoryService->calculateAvailableStock($item->productVariant) < $item->quantity) {
                throw new RuntimeException('Cart contains a product variant with insufficient stock.');
            }
        }
    }

    private function validateCheckoutData(array $data): void
    {
        if (empty($data['payment_method'])) {
            throw new RuntimeException('Payment method is required.');
        }

        if (empty($data['address']) || ! is_array($data['address'])) {
            throw new RuntimeException('Shipping address snapshot is required.');
        }
    }

    private function resolveCurrency(?int $currencyId = null, ?string $currencyCode = null): Currency
    {
        return Currency::query()
            ->when($currencyId !== null, fn ($query) => $query->whereKey($currencyId))
            ->when($currencyId === null && $currencyCode !== null, fn ($query) => $query->where('code', $currencyCode))
            ->when($currencyId === null && $currencyCode === null, fn ($query) => $query->where('is_default', true))
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function resolveShippingFee(array $data): float
    {
        if (! empty($data['shipping_rate_id'])) {
            $rate = ShippingRate::query()
                ->whereKey($data['shipping_rate_id'])
                ->where('is_active', true)
                ->firstOrFail();

            return (float) $rate->fee;
        }

        return 0.0;
    }

    private function resolveCoupon(Coupon|int|string $coupon): Coupon
    {
        if ($coupon instanceof Coupon) {
            return $coupon;
        }

        return Coupon::query()
            ->when(is_numeric($coupon), fn ($query) => $query->whereKey($coupon))
            ->when(! is_numeric($coupon), fn ($query) => $query->where('code', $coupon))
            ->firstOrFail();
    }

    private function resolveCart(Cart|int $cart): Cart
    {
        if ($cart instanceof Cart) {
            return $cart;
        }

        return Cart::query()->findOrFail($cart);
    }
}
