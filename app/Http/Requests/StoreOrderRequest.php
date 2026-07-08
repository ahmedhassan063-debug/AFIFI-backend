<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_number' => ['required', 'string', 'max:30', Rule::unique('orders', 'order_number')],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:20'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'currency_code' => ['required', 'string', 'size:3'],
            'exchange_rate' => ['required', 'numeric', 'min:0', 'decimal:0,8'],
            'status' => ['sometimes', 'string', 'max:30'],
            'payment_status' => ['sometimes', 'string', 'max:30'],
            'payment_method' => ['required', 'string', 'max:30'],
            'subtotal' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'shipping_fee' => ['sometimes', 'numeric', 'min:0', 'decimal:0,2'],
            'discount_total' => ['sometimes', 'numeric', 'min:0', 'decimal:0,2'],
            'grand_total' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'coupon_id' => ['nullable', 'integer', 'exists:coupons,id'],
            'customer_notes' => ['nullable', 'string'],
            'admin_notes' => ['nullable', 'string'],
            'whatsapp_sent_at' => ['nullable', 'date'],
            'confirmed_at' => ['nullable', 'date'],
            'cancelled_at' => ['nullable', 'date'],
        ];
    }
}
