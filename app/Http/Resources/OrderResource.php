<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ] : null),
            'guest_email' => $this->guest_email,
            'guest_phone' => $this->guest_phone,
            'currency_id' => $this->currency_id,
            'currency_code' => $this->currency_code,
            'exchange_rate' => $this->exchange_rate,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'subtotal' => $this->subtotal,
            'shipping_fee' => $this->shipping_fee,
            'discount_total' => $this->discount_total,
            'grand_total' => $this->grand_total,
            'coupon_id' => $this->coupon_id,
            'customer_notes' => $this->customer_notes,
            'admin_notes' => $this->when(
                $request->user()?->can('orders.view'),
                $this->admin_notes
            ),
            'whatsapp_sent_at' => $this->whatsapp_sent_at,
            'confirmed_at' => $this->confirmed_at,
            'cancelled_at' => $this->cancelled_at,
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
            'coupon' => new CouponResource($this->whenLoaded('coupon')),
            'addresses' => OrderAddressResource::collection($this->whenLoaded('addresses')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'status_history' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'shipment' => new OrderShipmentResource($this->whenLoaded('shipment')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'refunds' => RefundResource::collection($this->whenLoaded('refunds')),
            'return_requests' => ReturnRequestResource::collection($this->whenLoaded('returnRequests')),
            'coupon_redemption' => new CouponRedemptionResource($this->whenLoaded('couponRedemption')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
