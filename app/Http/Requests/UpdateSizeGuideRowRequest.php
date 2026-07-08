<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSizeGuideRowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $row = $this->route('size_guide_row') ?? $this->route('sizeGuideRow');
        $rowId = is_object($row) ? $row->getKey() : $row;
        $sizeGuideId = $this->input('size_guide_id', is_object($row) ? $row->size_guide_id : null);
        $sizeId = $this->input('size_id', is_object($row) ? $row->size_id : null);

        return [
            'size_guide_id' => ['sometimes', 'required', 'integer', 'exists:size_guides,id'],
            'size_id' => ['sometimes', 'required', 'integer', 'exists:sizes,id'],
            'measurement_type' => [
                'sometimes',
                'required',
                Rule::in(['chest', 'length', 'shoulder', 'waist', 'hip', 'inseam']),
                Rule::unique('size_guide_rows', 'measurement_type')
                    ->where(fn ($query) => $query->where('size_guide_id', $sizeGuideId)->where('size_id', $sizeId))
                    ->ignore($rowId),
            ],
            'value_cm' => ['sometimes', 'required', 'numeric', 'min:0', 'max:9999.99', 'decimal:0,2'],
        ];
    }
}
