<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'campaign_id' => ['required', 'integer', 'exists:campaigns,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => [
                'nullable',
                'integer',
                'exists:product_variants,id',
                Rule::unique('campaign_products', 'product_variant_id')->where(fn ($query) => $query
                    ->where('campaign_id', $this->input('campaign_id'))
                    ->where('product_id', $this->input('product_id'))),
            ],
            'campaign_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'stock_limit' => ['nullable', 'integer', 'min:0'],
            'sold_count' => ['sometimes', 'integer', 'min:0'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
