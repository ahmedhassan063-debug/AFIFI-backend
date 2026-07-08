<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'product_variant_id' => $this->product_variant_id,
            'product_name' => $this->product_name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'color_name' => $this->color_name,
            'size_name' => $this->size_name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'line_total' => $this->line_total,
            'product_variant' => new ProductVariantResource($this->whenLoaded('productVariant')),
            'return_requests' => ReturnRequestResource::collection($this->whenLoaded('returnRequests')),
            'created_at' => $this->created_at,
        ];
    }
}
