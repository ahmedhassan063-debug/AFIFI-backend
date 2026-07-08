<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'description' => $this->description,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'causer_type' => $this->causer_type,
            'causer_id' => $this->causer_id,
            'properties' => $this->properties,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'subject' => $this->whenLoaded('subject', fn () => [
                'id' => $this->subject?->getKey(),
                'type' => $this->subject_type,
            ]),
            'causer' => $this->whenLoaded('causer', fn () => [
                'id' => $this->causer?->getKey(),
                'type' => $this->causer_type,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
