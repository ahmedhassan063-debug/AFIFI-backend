<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaign_id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'campaign_price' => $this->campaign_price,
            'stock_limit' => $this->stock_limit,
            'sold_count' => $this->sold_count,
            'sort_order' => $this->sort_order,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'base_price' => $this->product->base_price,
            ]),
            'product_variant' => new ProductVariantResource($this->whenLoaded('productVariant')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
