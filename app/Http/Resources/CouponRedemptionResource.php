<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponRedemptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'coupon_id' => $this->coupon_id,
            'order_id' => $this->order_id,
            'user_id' => $this->user_id,
            'discount_amount' => $this->discount_amount,
            'coupon' => new CouponResource($this->whenLoaded('coupon')),
            'created_at' => $this->created_at,
        ];
    }
}
