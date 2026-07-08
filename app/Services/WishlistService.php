<?php

namespace App\Services;

use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Collection;

class WishlistService
{
    public function getOrCreateWishlistForUser(int $userId): Wishlist
    {
        return Wishlist::query()->firstOrCreate([
            'user_id' => $userId,
        ]);
    }

    public function addItem(int $userId, int $productId, ?int $productVariantId = null): WishlistItem
    {
        $wishlist = $this->getOrCreateWishlistForUser($userId);

        $query = WishlistItem::query()
            ->where('wishlist_id', $wishlist->id)
            ->where('product_id', $productId);

        if ($productVariantId === null) {
            $query->whereNull('product_variant_id');
        } else {
            $query->where('product_variant_id', $productVariantId);
        }

        $existingItem = $query->first();

        if ($existingItem) {
            return $existingItem;
        }

        return WishlistItem::query()->create([
            'wishlist_id' => $wishlist->id,
            'product_id' => $productId,
            'product_variant_id' => $productVariantId,
        ]);
    }

    public function removeItem(int $userId, int $wishlistItemId): bool
    {
        $wishlist = $this->getOrCreateWishlistForUser($userId);

        return (bool) WishlistItem::query()
            ->where('wishlist_id', $wishlist->id)
            ->where('id', $wishlistItemId)
            ->delete();
    }

    public function listItems(int $userId): Collection
    {
        $wishlist = $this->getOrCreateWishlistForUser($userId);

        return $wishlist->items()
            ->with(['product', 'productVariant'])
            ->latest('created_at')
            ->get();
    }
}
