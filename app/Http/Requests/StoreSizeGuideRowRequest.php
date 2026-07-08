<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSizeGuideRowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'size_guide_id' => ['required', 'integer', 'exists:size_guides,id'],
            'size_id' => ['required', 'integer', 'exists:sizes,id'],
            'measurement_type' => [
                'required',
                Rule::in(['chest', 'length', 'shoulder', 'waist', 'hip', 'inseam']),
                Rule::unique('size_guide_rows', 'measurement_type')->where(fn ($query) => $query
                    ->where('size_guide_id', $this->input('size_guide_id'))
                    ->where('size_id', $this->input('size_id'))),
            ],
            'value_cm' => ['required', 'numeric', 'min:0', 'max:9999.99', 'decimal:0,2'],
        ];
    }
}
