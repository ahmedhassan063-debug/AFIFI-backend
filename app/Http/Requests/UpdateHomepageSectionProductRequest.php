<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHomepageSectionProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sectionProduct = $this->route('homepage_section_product') ?? $this->route('homepageSectionProduct');
        $sectionProductId = is_object($sectionProduct) ? $sectionProduct->getKey() : $sectionProduct;
        $sectionId = $this->input('homepage_section_id', is_object($sectionProduct) ? $sectionProduct->homepage_section_id : null);

        return [
            'homepage_section_id' => ['sometimes', 'required', 'integer', 'exists:homepage_sections,id'],
            'product_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:products,id',
                Rule::unique('homepage_section_products', 'product_id')
                    ->where(fn ($query) => $query->where('homepage_section_id', $sectionId))
                    ->ignore($sectionProductId),
            ],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
