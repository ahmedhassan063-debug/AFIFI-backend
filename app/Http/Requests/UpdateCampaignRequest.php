<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $campaign = $this->route('campaign');
        $campaignId = is_object($campaign) ? $campaign->getKey() : $campaign;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'slug' => ['sometimes', 'required', 'string', 'max:170', Rule::unique('campaigns', 'slug')->ignore($campaignId)],
            'description' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', 'required', Rule::in(['flash_sale', 'seasonal', 'clearance'])],
            'discount_type' => ['sometimes', Rule::in(['percent', 'fixed', 'none'])],
            'discount_value' => ['sometimes', 'nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'starts_at' => ['sometimes', 'required', 'date'],
            'ends_at' => ['sometimes', 'required', 'date', 'after:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'banner_media_id' => ['sometimes', 'nullable', 'integer', 'exists:media,id'],
        ];
    }
}
