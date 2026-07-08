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
        $preference = $this->route('admin_preference') ?? $this->route('adminPreference');
        $preferenceId = is_object($preference) ? $preference->getKey() : $preference;
        $userId = $this->input('user_id', is_object($preference) ? $preference->user_id : null);

        return [
            'user_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'key' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('admin_preferences', 'key')
                    ->where(fn ($query) => $query->where('user_id', $userId))
                    ->ignore($preferenceId),
            ],
            'value' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', Rule::in(['string', 'integer', 'decimal', 'boolean', 'json'])],
        ];
    }
}
