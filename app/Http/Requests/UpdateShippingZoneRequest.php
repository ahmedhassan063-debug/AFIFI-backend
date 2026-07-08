<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShippingZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $zone = $this->route('shipping_zone') ?? $this->route('shippingZone');
        $zoneId = is_object($zone) ? $zone->getKey() : $zone;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'code' => ['sometimes', 'required', 'string', 'max:30', Rule::unique('shipping_zones', 'code')->ignore($zoneId)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
