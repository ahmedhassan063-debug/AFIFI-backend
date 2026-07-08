<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'placement' => ['sometimes', 'string', 'max:50'],
            'title' => ['sometimes', 'nullable', 'string', 'max:200'],
            'subtitle' => ['sometimes', 'nullable', 'string', 'max:300'],
            'desktop_media_id' => ['sometimes', 'required', 'integer', 'exists:media,id'],
            'mobile_media_id' => ['sometimes', 'nullable', 'integer', 'exists:media,id'],
            'button_text' => ['sometimes', 'nullable', 'string', 'max:80'],
            'button_link' => ['sometimes', 'nullable', 'string', 'max:500'],
            'priority' => ['sometimes', 'integer', 'min:0'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
