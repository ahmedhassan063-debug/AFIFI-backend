<?php

namespace App\Policies;

use App\Models\ProductVariant;
use App\Models\User;

class ProductVariantPolicy
{
    public function viewAny(?User $user = null): bool
    {
        return true;
    }

    public function view(?User $user, ProductVariant $productVariant): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('products.create');
    }

    public function update(User $user, ProductVariant $productVariant): bool
    {
        return $user->can('products.update');
    }

    public function delete(User $user, ProductVariant $productVariant): bool
    {
        return $user->can('products.delete');
    }

    public function updateInventory(User $user, ProductVariant $productVariant): bool
    {
        return $user->can('inventory.update');
    }
}
