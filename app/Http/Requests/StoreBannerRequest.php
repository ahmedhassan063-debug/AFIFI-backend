<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'placement' => ['sometimes', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:300'],
            'desktop_media_id' => ['required', 'integer', 'exists:media,id'],
            'mobile_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'button_text' => ['nullable', 'string', 'max:80'],
            'button_link' => ['nullable', 'string', 'max:500'],
            'priority' => ['sometimes', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
