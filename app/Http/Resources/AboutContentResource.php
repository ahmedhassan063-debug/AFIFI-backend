<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AboutContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'story_media_id' => $this->story_media_id,
            'story_title' => $this->story_title,
            'story_body' => $this->story_body,
            'story_media' => new MediaResource($this->whenLoaded('storyMedia')),
            'updated_at' => $this->updated_at,
        ];
    }
}
