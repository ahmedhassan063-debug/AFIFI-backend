<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHomepageSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $section = $this->route('homepage_section') ?? $this->route('homepageSection');
        $sectionId = is_object($section) ? $section->getKey() : $section;

        return [
            'key' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('homepage_sections', 'key')->ignore($sectionId)],
            'title' => ['sometimes', 'nullable', 'string', 'max:200'],
            'subtitle' => ['sometimes', 'nullable', 'string', 'max:300'],
            'body' => ['sometimes', 'nullable', 'string'],
            'media_id' => ['sometimes', 'nullable', 'integer', 'exists:media,id'],
            'cta_text' => ['sometimes', 'nullable', 'string', 'max:80'],
            'cta_url' => ['sometimes', 'nullable', 'string', 'max:500'],
            'config' => ['sometimes', 'nullable', 'array'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
