<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['sometimes', 'required', 'integer', 'exists:orders,id'],
            'provider' => ['sometimes', 'required', 'string', 'max:30'],
            'provider_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0', 'decimal:0,2'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'status' => ['sometimes', 'string', 'max:30'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'paid_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
