<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $shipment = $this->route('order_shipment') ?? $this->route('orderShipment');
        $shipmentId = is_object($shipment) ? $shipment->getKey() : $shipment;

        return [
            'order_id' => ['sometimes', 'required', 'integer', 'exists:orders,id', Rule::unique('order_shipments', 'order_id')->ignore($shipmentId)],
            'carrier' => ['sometimes', 'nullable', 'string', 'max:100'],
            'tracking_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'shipped_at' => ['sometimes', 'nullable', 'date'],
            'delivered_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:shipped_at'],
        ];
    }
}
