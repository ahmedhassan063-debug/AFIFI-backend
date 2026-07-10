<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', Rule::in(['string', 'integer', 'decimal', 'boolean', 'json'])],
        ];
    }
}
