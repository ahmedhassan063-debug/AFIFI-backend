<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $this->owns($user, $order) || $user->can('orders.view');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('orders.update');
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->can('orders.update');
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->owns($user, $order) || $user->can('orders.update');
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $user->can('orders.update');
    }

    private function owns(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }
}
