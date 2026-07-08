<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brand_id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'gender' => $this->gender,
            'badge' => $this->badge,
            'base_price' => $this->base_price,
            'compare_at_price' => $this->compare_at_price,
            'has_variants' => $this->has_variants,
            'is_active' => $this->is_active,
            'is_new_arrival' => $this->is_new_arrival,
            'is_best_seller' => $this->is_best_seller,
            'is_featured_drop' => $this->is_featured_drop,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'published_at' => $this->published_at,
            'sort_order' => $this->sort_order,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'collections' => CollectionResource::collection($this->whenLoaded('collections')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
