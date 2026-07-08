<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WishlistItem;

class WishlistItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, WishlistItem $wishlistItem): bool
    {
        return $this->owns($user, $wishlistItem) || $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, WishlistItem $wishlistItem): bool
    {
        return $this->owns($user, $wishlistItem) || $user->can('users.update');
    }

    public function delete(User $user, WishlistItem $wishlistItem): bool
    {
        return $this->owns($user, $wishlistItem) || $user->can('users.update');
    }

    private function owns(User $user, WishlistItem $wishlistItem): bool
    {
        return $wishlistItem->wishlist?->user_id === $user->id;
    }
}
