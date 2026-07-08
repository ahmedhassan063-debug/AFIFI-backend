<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SizeGuideRowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'size_guide_id' => $this->size_guide_id,
            'size_id' => $this->size_id,
            'measurement_type' => $this->measurement_type,
            'value_cm' => $this->value_cm,
            'size' => new SizeResource($this->whenLoaded('size')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
