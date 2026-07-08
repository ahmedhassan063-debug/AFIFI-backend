<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'type' => $this->type,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'governorate_name' => $this->governorate_name,
            'shipping_zone_code' => $this->shipping_zone_code,
            'city' => $this->city,
            'area' => $this->area,
            'street' => $this->street,
            'building' => $this->building,
            'floor' => $this->floor,
            'postal_code' => $this->postal_code,
            'created_at' => $this->created_at,
        ];
    }
}
