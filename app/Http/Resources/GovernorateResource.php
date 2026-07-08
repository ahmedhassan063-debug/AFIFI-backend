<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GovernorateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shipping_zone_id' => $this->shipping_zone_id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'is_active' => $this->is_active,
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
