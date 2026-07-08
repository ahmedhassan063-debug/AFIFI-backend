<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'label' => ['sometimes', 'nullable', 'string', 'max:50'],
            'full_name' => ['sometimes', 'required', 'string', 'max:150'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'governorate_id' => ['sometimes', 'required', 'integer', 'exists:governorates,id'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'area' => ['sometimes', 'nullable', 'string', 'max:150'],
            'street' => ['sometimes', 'required', 'string', 'max:200'],
            'building' => ['sometimes', 'nullable', 'string', 'max:50'],
            'floor' => ['sometimes', 'nullable', 'string', 'max:20'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
