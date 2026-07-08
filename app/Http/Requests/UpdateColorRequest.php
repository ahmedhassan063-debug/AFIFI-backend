<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $color = $this->route('color');
        $colorId = is_object($color) ? $color->getKey() : $color;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('colors', 'name')->ignore($colorId)],
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('colors', 'slug')->ignore($colorId)],
            'hex_code' => ['sometimes', 'required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
