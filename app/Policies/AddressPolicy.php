<?php

namespace App\Policies;

use App\Models\Address;
use App\Models\User;

class AddressPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Address $address): bool
    {
        return $this->owns($user, $address) || $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Address $address): bool
    {
        return $this->owns($user, $address) || $user->can('users.update');
    }

    public function delete(User $user, Address $address): bool
    {
        return $this->owns($user, $address) || $user->can('users.update');
    }

    public function setDefault(User $user, Address $address): bool
    {
        return $this->update($user, $address);
    }

    private function owns(User $user, Address $address): bool
    {
        return $address->user_id === $user->id;
    }
}
