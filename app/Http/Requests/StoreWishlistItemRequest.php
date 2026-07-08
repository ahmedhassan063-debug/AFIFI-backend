<?php

namespace App\Http\Requests;

use App\Models\Wishlist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWishlistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $wishlistId = Wishlist::query()->where('user_id', $this->user()?->id)->value('id');

        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => [
                'nullable',
                'integer',
                'exists:product_variants,id',
                Rule::unique('wishlist_items', 'product_variant_id')->where(fn ($query) => $query
                    ->where('wishlist_id', $wishlistId)
                    ->where('product_id', $this->input('product_id'))),
            ],
        ];
    }
}
