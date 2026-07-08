<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShippingRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_zone_id' => ['sometimes', 'required', 'integer', 'exists:shipping_zones,id'],
            'fee' => ['sometimes', 'required', 'numeric', 'min:0', 'decimal:0,2'],
            'estimated_days_min' => ['sometimes', 'required', 'integer', 'min:0', 'max:255'],
            'estimated_days_max' => ['sometimes', 'required', 'integer', 'min:0', 'max:255', 'gte:estimated_days_min'],
            'is_active' => ['sometimes', 'boolean'],
            'valid_from' => ['sometimes', 'nullable', 'date'],
            'valid_until' => ['sometimes', 'nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }
}
