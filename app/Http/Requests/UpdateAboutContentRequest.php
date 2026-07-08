<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAboutContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'story_media_id' => ['sometimes', 'nullable', 'integer', 'exists:media,id'],
            'story_title' => ['sometimes', 'required', 'string', 'max:200'],
            'story_body' => ['sometimes', 'required', 'string'],
        ];
    }
}
