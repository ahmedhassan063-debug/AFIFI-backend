<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'color_id' => $this->color_id,
            'size_id' => $this->size_id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price_override' => $this->price_override,
            'stock' => $this->stock,
            'is_active' => $this->is_active,
            'color' => new ColorResource($this->whenLoaded('color')),
            'size' => new SizeResource($this->whenLoaded('size')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
