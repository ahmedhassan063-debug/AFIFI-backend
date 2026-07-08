<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'provider' => ['required', 'string', 'max:30'],
            'provider_reference' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'status' => ['sometimes', 'string', 'max:30'],
            'metadata' => ['nullable', 'array'],
            'paid_at' => ['nullable', 'date'],
        ];
    }
}
