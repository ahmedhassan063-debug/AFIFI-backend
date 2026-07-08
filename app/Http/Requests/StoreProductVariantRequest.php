<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'color_id' => ['required', 'integer', 'exists:colors,id'],
            'size_id' => [
                'required',
                'integer',
                'exists:sizes,id',
                Rule::unique('product_variants', 'size_id')->where(fn ($query) => $query
                    ->where('product_id', $this->input('product_id'))
                    ->where('color_id', $this->input('color_id'))),
            ],
            'sku' => ['required', 'string', 'max:255', Rule::unique('product_variants', 'sku')],
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'barcode')],
            'price_override' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
