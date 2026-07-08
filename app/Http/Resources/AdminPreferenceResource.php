<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'key' => $this->key,
            'value' => $this->value,
            'type' => $this->type,
            'updated_at' => $this->updated_at,
        ];
    }
}
