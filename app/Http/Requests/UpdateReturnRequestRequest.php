<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReturnRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['sometimes', 'required', 'integer', 'exists:orders,id'],
            'order_item_id' => ['sometimes', 'required', 'integer', 'exists:order_items,id'],
            'type' => ['sometimes', 'required', Rule::in(['exchange', 'return'])],
            'reason' => ['sometimes', 'required', 'string', 'max:500'],
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected', 'completed'])],
            'admin_notes' => ['sometimes', 'nullable', 'string'],
            'requested_at' => ['sometimes', 'date'],
            'resolved_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
