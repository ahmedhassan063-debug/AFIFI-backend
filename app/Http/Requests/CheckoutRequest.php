<?php

namespace App\Http\Requests;

use App\Services\ManualPaymentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'currency_id' => ['sometimes', 'integer', 'exists:currencies,id'],
            'currency_code' => ['sometimes', 'string', 'size:3'],
            'shipping_rate_id' => ['sometimes', 'integer', 'exists:shipping_rates,id'],
            'coupon_id' => ['sometimes', 'integer', 'exists:coupons,id'],
            'coupon_code' => ['sometimes', 'string', 'max:50'],
            'payment_method' => ['required', 'string', 'max:30', Rule::in(ManualPaymentService::SUPPORTED_METHODS)],
            'payment_provider' => ['prohibited'],
            'payment_metadata' => ['sometimes', 'nullable', 'array'],
            'customer_notes' => ['sometimes', 'nullable', 'string'],
            'reservation_expires_at' => ['sometimes', 'date'],
            'address' => ['required', 'array'],
            'address.type' => ['sometimes', 'string', 'in:shipping,billing'],
            'address.full_name' => ['required', 'string', 'max:150'],
            'address.phone' => ['required', 'string', 'max:20'],
            'address.governorate_name' => ['required', 'string', 'max:100'],
            'address.shipping_zone_code' => ['required', 'string', 'max:30'],
            'address.city' => ['required', 'string', 'max:100'],
            'address.area' => ['sometimes', 'nullable', 'string', 'max:150'],
            'address.street' => ['required', 'string', 'max:200'],
            'address.building' => ['sometimes', 'nullable', 'string', 'max:50'],
            'address.floor' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address.postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }
}
