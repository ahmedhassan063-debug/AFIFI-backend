<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePolicyPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $policyPage = $this->route('policy_page') ?? $this->route('policyPage');
        $policyPageId = is_object($policyPage) ? $policyPage->getKey() : $policyPage;

        return [
            'slug' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('policy_pages', 'slug')->ignore($policyPageId)],
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            'content' => ['sometimes', 'required', 'string'],
        ];
    }
}
