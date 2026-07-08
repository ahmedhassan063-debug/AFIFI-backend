<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public const array STATUSES = [
        'pending_confirmation',
        'confirmed',
        'processing',
        'shipped',
        'delivered',
        'cancelled',
        'returned',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(self::STATUSES)],
            'payment_status' => ['sometimes', 'string', 'max:30'],
            'admin_notes' => ['sometimes', 'nullable', 'string'],
            'whatsapp_sent_at' => ['sometimes', 'nullable', 'date'],
            'confirmed_at' => ['sometimes', 'nullable', 'date'],
            'cancelled_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
