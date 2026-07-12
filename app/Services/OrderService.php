<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\OrderStatusHistory;
use App\Models\ReturnRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OrderService
{
    private const array STATUS_TRANSITIONS = [
        'pending_confirmation' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'returned'],
        'delivered' => ['returned'],
        'cancelled' => [],
        'returned' => [],
    ];

    private const array CANCELLATION_RESTOCK_STATUSES = [
        'pending_confirmation',
        'confirmed',
        'processing',
    ];

    private const string RETURN_ELIGIBLE_ORDER_STATUS = 'delivered';

    private const array ACTIVE_RETURN_REQUEST_STATUSES = [
        'pending',
        'approved',
        'completed',
    ];

    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function createOrderFromCartSnapshot(Cart|int $cart, array $orderData, array $addressData): Order
    {
        return DB::transaction(function () use ($cart, $orderData, $addressData) {
            $cart = $this->resolveCart($cart);
            $cart->loadMissing(['items.productVariant.product', 'items.productVariant.color', 'items.productVariant.size']);

            $order = Order::query()->create(array_merge(
                Arr::only($orderData, [
                    'user_id',
                    'guest_email',
                    'guest_phone',
                    'currency_id',
                    'currency_code',
                    'exchange_rate',
                    'status',
                    'payment_status',
                    'payment_method',
                    'subtotal',
                    'shipping_fee',
                    'discount_total',
                    'grand_total',
                    'coupon_id',
                    'customer_notes',
                    'admin_notes',
                    'whatsapp_sent_at',
                    'confirmed_at',
                    'cancelled_at',
                ]),
                [
                    'order_number' => $orderData['order_number'] ?? $this->generateOrderNumber(),
                ]
            ));

            $order->addresses()->create(array_merge(
                Arr::only($addressData, [
                    'full_name',
                    'phone',
                    'governorate_name',
                    'shipping_zone_code',
                    'city',
                    'area',
                    'street',
                    'building',
                    'floor',
                    'postal_code',
                ]),
                ['type' => 'shipping']
            ));

            foreach ($cart->items as $cartItem) {
                $variant = $cartItem->productVariant;

                $order->items()->create([
                    'product_variant_id' => $variant?->id,
                    'product_name' => $variant?->product?->name ?? '',
                    'sku' => $variant?->sku ?? '',
                    'barcode' => $variant?->barcode,
                    'color_name' => $variant?->color?->name ?? '',
                    'size_name' => $variant?->size?->name ?? '',
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price_snapshot,
                    'line_total' => round((float) $cartItem->unit_price_snapshot * $cartItem->quantity, 2),
                ]);
            }

            $this->recordStatusHistory(
                $order,
                null,
                $order->status,
                'Order created from cart snapshot.',
            );

            return $order->refresh();
        });
    }

    public function updateOrderStatus(Order|int $order, string $status, ?string $note = null, ?int $changedBy = null): Order
    {
        return DB::transaction(function () use ($order, $status, $note, $changedBy) {
            $order = $this->resolveOrder($order);
            $fromStatus = $order->status;
            $this->assertValidStatusTransition($fromStatus, $status);
            $attributes = ['status' => $status];

            if ($status === 'confirmed') {
                $attributes['confirmed_at'] = now();
            }

            if ($status === 'cancelled') {
                $attributes['cancelled_at'] = now();
            }

            $order->update($attributes);

            if ($status === 'cancelled' && in_array($fromStatus, self::CANCELLATION_RESTOCK_STATUSES, true)) {
                $this->restoreInventoryForCancelledOrder($order);
            }

            $this->recordStatusHistory($order, $fromStatus, $status, $note, $changedBy);

            return $order->refresh();
        });
    }

    public function recordStatusHistory(
        Order|int $order,
        ?string $fromStatus,
        string $toStatus,
        ?string $note = null,
        ?int $changedBy = null
    ): OrderStatusHistory {
        $order = $this->resolveOrder($order);

        return OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'note' => $note,
            'changed_by' => $changedBy,
        ]);
    }

    public function createOrUpdateShipment(Order|int $order, array $data): OrderShipment
    {
        $order = $this->resolveOrder($order);

        return OrderShipment::query()->updateOrCreate(
            ['order_id' => $order->id],
            Arr::only($data, [
                'carrier',
                'tracking_number',
                'shipped_at',
                'delivered_at',
            ])
        );
    }

    public function createReturnRequest(Order|int $order, array $data): ReturnRequest
    {
        return DB::transaction(function () use ($order, $data) {
            $order = $this->resolveOrder($order);
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $orderItemId = (int) $data['order_item_id'];

            if ($order->status !== self::RETURN_ELIGIBLE_ORDER_STATUS) {
                throw new RuntimeException('Returns can only be requested for delivered orders.');
            }

            $activeReturnExists = ReturnRequest::query()
                ->where('order_id', $order->id)
                ->where('order_item_id', $orderItemId)
                ->whereIn('status', self::ACTIVE_RETURN_REQUEST_STATUSES)
                ->lockForUpdate()
                ->exists();

            if ($activeReturnExists) {
                throw new RuntimeException('A return request already exists for this item.');
            }

            return ReturnRequest::query()->create(array_merge(
                Arr::only($data, [
                    'order_item_id',
                    'type',
                    'reason',
                    'admin_notes',
                    'requested_at',
                    'resolved_at',
                ]),
                [
                    'order_id' => $order->id,
                    'status' => 'pending',
                    'requested_at' => $data['requested_at'] ?? now(),
                ]
            ));
        });
    }

    public function updateReturnRequestStatus(
        ReturnRequest|int $returnRequest,
        string $status,
        ?string $adminNotes = null,
        mixed $resolvedAt = null
    ): ReturnRequest {
        $returnRequest = $this->resolveReturnRequest($returnRequest);

        $returnRequest->update([
            'status' => $status,
            'admin_notes' => $adminNotes ?? $returnRequest->admin_notes,
            'resolved_at' => $resolvedAt ?? (in_array($status, ['approved', 'rejected', 'completed'], true) ? now() : $returnRequest->resolved_at),
        ]);

        return $returnRequest->refresh();
    }

    private function assertValidStatusTransition(string $fromStatus, string $toStatus): void
    {
        $allowedTransitions = self::STATUS_TRANSITIONS[$fromStatus] ?? null;

        if ($allowedTransitions === null || ! in_array($toStatus, $allowedTransitions, true)) {
            throw new RuntimeException("Invalid order status transition from \"{$fromStatus}\" to \"{$toStatus}\".");
        }
    }

    private function restoreInventoryForCancelledOrder(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if (! $item->product_variant_id || $item->quantity < 1) {
                continue;
            }

            $this->inventoryService->restock(
                $item->product_variant_id,
                $item->quantity,
                'Order cancelled.',
                null,
                Order::class,
                $order->id
            );
        }
    }

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'AFIFI-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    private function resolveCart(Cart|int $cart): Cart
    {
        if ($cart instanceof Cart) {
            return $cart;
        }

        return Cart::query()->findOrFail($cart);
    }

    private function resolveOrder(Order|int $order): Order
    {
        if ($order instanceof Order) {
            return $order;
        }

        return Order::query()->findOrFail($order);
    }

    private function resolveReturnRequest(ReturnRequest|int $returnRequest): ReturnRequest
    {
        if ($returnRequest instanceof ReturnRequest) {
            return $returnRequest;
        }

        return ReturnRequest::query()->findOrFail($returnRequest);
    }
}
