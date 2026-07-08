<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tag = $this->route('tag');
        $tagId = is_object($tag) ? $tag->getKey() : $tag;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('tags', 'name')->ignore($tagId)],
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('tags', 'slug')->ignore($tagId)],
        ];
    }
}
