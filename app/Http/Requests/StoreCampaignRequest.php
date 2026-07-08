<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:170', Rule::unique('campaigns', 'slug')],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['flash_sale', 'seasonal', 'clearance'])],
            'discount_type' => ['sometimes', Rule::in(['percent', 'fixed', 'none'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'banner_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ];
    }
}
