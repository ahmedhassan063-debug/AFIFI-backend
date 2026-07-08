<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $coupon = $this->route('coupon');
        $couponId = is_object($coupon) ? $coupon->getKey() : $coupon;
        $effectiveType = $this->input('type', is_object($coupon) ? $coupon->type : null);

        return [
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($couponId)],
            'type' => ['sometimes', 'required', Rule::in(['percent', 'fixed'])],
            'value' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
                Rule::when($effectiveType === 'percent', ['max:100']),
            ],
            'min_order_total' => ['sometimes', 'nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'max_discount' => ['sometimes', 'nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'usage_limit' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
