<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGovernorateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $governorate = $this->route('governorate');
        $governorateId = is_object($governorate) ? $governorate->getKey() : $governorate;
        $shippingZoneId = $this->input('shipping_zone_id', is_object($governorate) ? $governorate->shipping_zone_id : null);

        return [
            'shipping_zone_id' => ['sometimes', 'required', 'integer', 'exists:shipping_zones,id'],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('governorates', 'name')
                    ->where(fn ($query) => $query->where('shipping_zone_id', $shippingZoneId))
                    ->ignore($governorateId),
            ],
            'name_ar' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
