<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPaymentReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('provider_reference')) {
            $this->merge([
                'provider_reference' => trim((string) $this->input('provider_reference')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'provider_reference' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }
}
