<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $variant = $this->route('product_variant') ?? $this->route('productVariant');
        $variantId = is_object($variant) ? $variant->getKey() : $variant;
        $productId = $this->input('product_id', is_object($variant) ? $variant->product_id : null);
        $colorId = $this->input('color_id', is_object($variant) ? $variant->color_id : null);

        return [
            'product_id' => ['sometimes', 'required', 'integer', 'exists:products,id'],
            'color_id' => ['sometimes', 'required', 'integer', 'exists:colors,id'],
            'size_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:sizes,id',
                Rule::unique('product_variants', 'size_id')
                    ->where(fn ($query) => $query->where('product_id', $productId)->where('color_id', $colorId))
                    ->ignore($variantId),
            ],
            'sku' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('product_variants', 'sku')->ignore($variantId)],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('product_variants', 'barcode')->ignore($variantId)],
            'price_override' => ['sometimes', 'nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
