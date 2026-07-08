<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $campaignProduct = $this->route('campaign_product') ?? $this->route('campaignProduct');
        $campaignProductId = is_object($campaignProduct) ? $campaignProduct->getKey() : $campaignProduct;
        $campaignId = $this->input('campaign_id', is_object($campaignProduct) ? $campaignProduct->campaign_id : null);
        $productId = $this->input('product_id', is_object($campaignProduct) ? $campaignProduct->product_id : null);

        return [
            'campaign_id' => ['sometimes', 'required', 'integer', 'exists:campaigns,id'],
            'product_id' => ['sometimes', 'required', 'integer', 'exists:products,id'],
            'product_variant_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:product_variants,id',
                Rule::unique('campaign_products', 'product_variant_id')
                    ->where(fn ($query) => $query->where('campaign_id', $campaignId)->where('product_id', $productId))
                    ->ignore($campaignProductId),
            ],
            'campaign_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'stock_limit' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'sold_count' => ['sometimes', 'integer', 'min:0'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
