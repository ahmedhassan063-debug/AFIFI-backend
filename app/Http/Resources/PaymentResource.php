<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'provider' => $this->provider,
            'provider_reference' => $this->provider_reference,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'paid_at' => $this->paid_at,
            'refunds' => RefundResource::collection($this->whenLoaded('refunds')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
