<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNewsletterSubscriberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', Rule::unique('newsletter_subscribers', 'email')],
            'source' => ['nullable', 'string', 'max:50'],
            'subscribed_at' => ['sometimes', 'date'],
            'unsubscribed_at' => ['nullable', 'date'],
        ];
    }
}
