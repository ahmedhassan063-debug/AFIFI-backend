<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order_item_id' => $this->order_item_id,
            'type' => $this->type,
            'reason' => $this->reason,
            'status' => $this->status,
            'admin_notes' => $this->when(
                $request->user()?->can('orders.view'),
                $this->admin_notes
            ),
            'requested_at' => $this->requested_at,
            'resolved_at' => $this->resolved_at,
            'order_item' => $this->whenLoaded('orderItem', fn () => [
                'id' => $this->orderItem->id,
                'product_name' => $this->orderItem->product_name,
                'sku' => $this->orderItem->sku,
                'quantity' => $this->orderItem->quantity,
                'line_total' => $this->orderItem->line_total,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
