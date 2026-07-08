<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'key' => [
                'required',
                'string',
                'max:100',
                Rule::unique('admin_preferences', 'key')->where(fn ($query) => $query->where('user_id', $this->input('user_id'))),
            ],
            'value' => ['nullable', 'string'],
            'type' => ['sometimes', Rule::in(['string', 'integer', 'decimal', 'boolean', 'json'])],
        ];
    }
}
