<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'order_id' => $this->order_id,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'status' => $this->status,
            'processed_at' => $this->processed_at,
            'payment' => $this->whenLoaded('payment', fn () => [
                'id' => $this->payment->id,
                'provider' => $this->payment->provider,
                'provider_reference' => $this->payment->provider_reference,
                'status' => $this->payment->status,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
