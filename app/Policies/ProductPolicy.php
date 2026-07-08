<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(?User $user = null): bool
    {
        return true;
    }

    public function view(?User $user, Product $product): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('products.delete');
    }

    public function publish(User $user, Product $product): bool
    {
        return $user->can('products.update');
    }

    public function unpublish(User $user, Product $product): bool
    {
        return $user->can('products.update');
    }

    public function updateFlags(User $user, Product $product): bool
    {
        return $user->can('products.update');
    }
}
