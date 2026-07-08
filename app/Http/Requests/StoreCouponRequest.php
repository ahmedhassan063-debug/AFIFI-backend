<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
                Rule::when($this->input('type') === 'percent', ['max:100']),
            ],
            'min_order_total' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'max_discount' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'usage_limit' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
