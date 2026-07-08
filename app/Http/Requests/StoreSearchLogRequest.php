<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSearchLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'max:255'],
            'results_count' => ['sometimes', 'integer', 'min:0'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'session_id' => ['nullable', 'string', 'max:100'],
            'clicked_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'ip_address' => ['nullable', 'ip', 'max:45'],
            'user_agent' => ['nullable', 'string', 'max:500'],
        ];
    }
}
