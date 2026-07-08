<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_id' => ['sometimes', 'required', 'integer', 'exists:payments,id'],
            'order_id' => ['sometimes', 'required', 'integer', 'exists:orders,id'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0', 'decimal:0,2'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
            'status' => ['sometimes', Rule::in(['pending', 'processed', 'failed'])],
            'processed_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
