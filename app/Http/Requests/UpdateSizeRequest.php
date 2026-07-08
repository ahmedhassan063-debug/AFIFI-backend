<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $size = $this->route('size');
        $sizeId = is_object($size) ? $size->getKey() : $size;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('sizes', 'name')->ignore($sizeId)],
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('sizes', 'slug')->ignore($sizeId)],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
