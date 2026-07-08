<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'logo_media_id' => $this->logo_media_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'website_url' => $this->website_url,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'logo_media' => new MediaResource($this->whenLoaded('logoMedia')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
