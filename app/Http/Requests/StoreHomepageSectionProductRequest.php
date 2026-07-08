<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHomepageSectionProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'homepage_section_id' => ['required', 'integer', 'exists:homepage_sections,id'],
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
                Rule::unique('homepage_section_products', 'product_id')->where(fn ($query) => $query
                    ->where('homepage_section_id', $this->input('homepage_section_id'))),
            ],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
