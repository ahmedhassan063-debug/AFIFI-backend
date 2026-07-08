<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'is_active' => $this->is_active,
            'banner_media_id' => $this->banner_media_id,
            'banner_media' => new MediaResource($this->whenLoaded('bannerMedia')),
            'campaign_products' => CampaignProductResource::collection($this->whenLoaded('campaignProducts')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
