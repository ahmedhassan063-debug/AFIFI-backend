<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wishlist;

class WishlistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, Wishlist $wishlist): bool
    {
        return $this->owns($user, $wishlist) || $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Wishlist $wishlist): bool
    {
        return $this->owns($user, $wishlist) || $user->can('users.update');
    }

    public function delete(User $user, Wishlist $wishlist): bool
    {
        return $this->owns($user, $wishlist) || $user->can('users.update');
    }

    private function owns(User $user, Wishlist $wishlist): bool
    {
        return $wishlist->user_id === $user->id;
    }
}
