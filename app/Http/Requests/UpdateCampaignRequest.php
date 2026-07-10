<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'ends_at' => ['sometimes', 'required', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'banner_media_id' => ['sometimes', 'nullable', 'integer', 'exists:media,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $campaign = $this->route('campaign');

            if (! is_object($campaign)) {
                return;
            }

            $startsAt = $this->input('starts_at', $campaign->starts_at);
            $endsAt = $this->input('ends_at', $campaign->ends_at);

            if ($startsAt !== null && $endsAt !== null && strtotime((string) $endsAt) <= strtotime((string) $startsAt)) {
                $validator->errors()->add('ends_at', 'The ends at field must be a date after starts at.');
            }
        });
    }
}
