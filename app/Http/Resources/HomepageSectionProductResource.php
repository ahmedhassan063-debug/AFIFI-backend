<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomepageSectionProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'homepage_section_id' => $this->homepage_section_id,
            'product_id' => $this->product_id,
            'sort_order' => $this->sort_order,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'base_price' => $this->product->base_price,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
