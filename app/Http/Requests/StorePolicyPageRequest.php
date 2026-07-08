<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePolicyPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:50', Rule::unique('policy_pages', 'slug')],
            'title' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string'],
        ];
    }
}
