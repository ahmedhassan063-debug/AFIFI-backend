<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $productId = is_object($product) ? $product->getKey() : $product;

        return [
            'brand_id' => ['sometimes', 'nullable', 'integer', 'exists:brands,id'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'short_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'description' => ['sometimes', 'nullable', 'string'],
            'gender' => ['sometimes', 'nullable', Rule::in(['men', 'women', 'unisex'])],
            'badge' => ['sometimes', 'nullable', Rule::in(['new', 'hot'])],
            'base_price' => ['sometimes', 'required', 'numeric', 'min:0', 'decimal:0,2'],
            'compare_at_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'has_variants' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'is_new_arrival' => ['sometimes', 'boolean'],
            'is_best_seller' => ['sometimes', 'boolean'],
            'is_featured_drop' => ['sometimes', 'boolean'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_description' => ['sometimes', 'nullable', 'string'],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
