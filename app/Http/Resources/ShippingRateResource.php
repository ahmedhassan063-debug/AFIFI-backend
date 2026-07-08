<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shipping_zone_id' => $this->shipping_zone_id,
            'fee' => $this->fee,
            'estimated_days_min' => $this->estimated_days_min,
            'estimated_days_max' => $this->estimated_days_max,
            'is_active' => $this->is_active,
            'valid_from' => $this->valid_from,
            'valid_until' => $this->valid_until,
            'shipping_zone' => $this->whenLoaded('shippingZone', fn () => [
                'id' => $this->shippingZone->id,
                'name' => $this->shippingZone->name,
                'code' => $this->shippingZone->code,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
