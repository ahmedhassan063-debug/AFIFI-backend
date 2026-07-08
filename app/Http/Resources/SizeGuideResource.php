<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SizeGuideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'rows' => SizeGuideRowResource::collection($this->whenLoaded('rows')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
