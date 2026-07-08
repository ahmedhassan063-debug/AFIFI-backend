<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'quantity_delta' => $this->quantity_delta,
            'stock_before' => $this->stock_after - $this->quantity_delta,
            'stock_after' => $this->stock_after,
            'reference_type' => $this->reference_type,
            'notes' => $this->notes,
            'product_variant' => $this->whenLoaded('productVariant', fn () => $this->productVariant ? [
                'id' => $this->productVariant->id,
                'sku' => $this->productVariant->sku,
                'product' => $this->productVariant->relationLoaded('product') && $this->productVariant->product ? [
                    'id' => $this->productVariant->product->id,
                    'name' => $this->productVariant->product->name,
                    'slug' => $this->productVariant->product->slug,
                ] : null,
                'color' => $this->productVariant->relationLoaded('color') ? optional($this->productVariant->color)->name : null,
                'size' => $this->productVariant->relationLoaded('size') ? optional($this->productVariant->size)->name : null,
            ] : null),
            'created_by' => $this->whenLoaded('createdBy', fn () => $this->createdBy ? [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
