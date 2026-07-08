<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShippingRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_zone_id' => ['required', 'integer', 'exists:shipping_zones,id'],
            'fee' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'estimated_days_min' => ['required', 'integer', 'min:0', 'max:255'],
            'estimated_days_max' => ['required', 'integer', 'min:0', 'max:255', 'gte:estimated_days_min'],
            'is_active' => ['sometimes', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }
}
