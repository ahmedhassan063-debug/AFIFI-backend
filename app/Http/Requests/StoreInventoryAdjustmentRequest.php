<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryAdjustmentRequest extends FormRequest
{
    /**
     * Authorization is handled by ProductVariantPolicy::updateInventory()
     * via the controller's $this->authorize() call.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // A zero delta is not a real adjustment - reject it explicitly
            // rather than silently recording a no-op movement.
            'quantity_delta' => ['required', 'integer', 'not_in:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
