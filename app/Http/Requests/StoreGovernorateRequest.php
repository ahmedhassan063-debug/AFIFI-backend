<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGovernorateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_zone_id' => ['required', 'integer', 'exists:shipping_zones,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('governorates', 'name')->where(fn ($query) => $query->where('shipping_zone_id', $this->input('shipping_zone_id'))),
            ],
            'name_ar' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
