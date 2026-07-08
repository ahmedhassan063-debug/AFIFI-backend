<?php

namespace App\Policies;

use App\Models\CartItem;
use App\Models\User;

class CartItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, CartItem $cartItem): bool
    {
        return $this->owns($user, $cartItem) || $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CartItem $cartItem): bool
    {
        return $this->owns($user, $cartItem) || $user->can('users.update');
    }

    public function delete(User $user, CartItem $cartItem): bool
    {
        return $this->owns($user, $cartItem) || $user->can('users.update');
    }

    private function owns(User $user, CartItem $cartItem): bool
    {
        return $cartItem->cart?->user_id === $user->id;
    }
}
