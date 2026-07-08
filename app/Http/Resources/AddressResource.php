<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'label' => $this->label,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'governorate_id' => $this->governorate_id,
            'city' => $this->city,
            'area' => $this->area,
            'street' => $this->street,
            'building' => $this->building,
            'floor' => $this->floor,
            'postal_code' => $this->postal_code,
            'is_default' => $this->is_default,
            'governorate' => new GovernorateResource($this->whenLoaded('governorate')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
