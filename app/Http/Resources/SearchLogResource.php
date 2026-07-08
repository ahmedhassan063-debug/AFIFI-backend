<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'query' => $this->query,
            'results_count' => $this->results_count,
            'user_id' => $this->user_id,
            'session_id' => $this->session_id,
            'clicked_product_id' => $this->clicked_product_id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'clicked_product' => $this->whenLoaded('clickedProduct', fn () => [
                'id' => $this->clickedProduct->id,
                'name' => $this->clickedProduct->name,
                'slug' => $this->clickedProduct->slug,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
