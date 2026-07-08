<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomepageSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'body' => $this->body,
            'media_id' => $this->media_id,
            'cta_text' => $this->cta_text,
            'cta_url' => $this->cta_url,
            'config' => $this->config,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'media' => new MediaResource($this->whenLoaded('media')),
            'section_products' => HomepageSectionProductResource::collection($this->whenLoaded('sectionProducts')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
