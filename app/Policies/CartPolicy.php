<?php

namespace App\Policies;

use App\Models\Cart;
use App\Models\User;

class CartPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, Cart $cart): bool
    {
        return $this->owns($user, $cart) || $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Cart $cart): bool
    {
        return $this->owns($user, $cart) || $user->can('users.update');
    }

    public function delete(User $user, Cart $cart): bool
    {
        return $this->owns($user, $cart) || $user->can('users.update');
    }

    public function clear(User $user, Cart $cart): bool
    {
        return $this->update($user, $cart);
    }

    private function owns(User $user, Cart $cart): bool
    {
        return $cart->user_id === $user->id;
    }
}
