<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'placement' => $this->placement,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'desktop_media_id' => $this->desktop_media_id,
            'mobile_media_id' => $this->mobile_media_id,
            'button_text' => $this->button_text,
            'button_link' => $this->button_link,
            'priority' => $this->priority,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'is_active' => $this->is_active,
            'desktop_media' => new MediaResource($this->whenLoaded('desktopMedia')),
            'mobile_media' => new MediaResource($this->whenLoaded('mobileMedia')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
