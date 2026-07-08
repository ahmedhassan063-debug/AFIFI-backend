<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->owns($user, $payment) || $user->can('payments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payments.view');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->can('payments.view');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }

    public function markAsPaid(User $user, Payment $payment): bool
    {
        return $user->can('payments.view');
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $user->can('payments.view');
    }

    private function owns(User $user, Payment $payment): bool
    {
        return $payment->order?->user_id === $user->id;
    }
}
