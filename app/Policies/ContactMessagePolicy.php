<?php

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;

class ContactMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('contact.view');
    }

    public function view(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can('contact.view');
    }

    public function updateStatus(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can('contact.manage');
    }

    public function delete(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can('contact.manage');
    }
}
