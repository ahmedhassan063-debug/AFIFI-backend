<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

class MediaPolicy
{
    public function viewAny(?User $user = null): bool
    {
        return true;
    }

    public function view(?User $user, Media $media): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->canManageMedia($user);
    }

    public function update(User $user, Media $media): bool
    {
        return $this->canManageMedia($user);
    }

    public function delete(User $user, Media $media): bool
    {
        return $this->canManageMedia($user);
    }

    private function canManageMedia(User $user): bool
    {
        return $user->can('products.create')
            || $user->can('products.update')
            || $user->can('cms.manage')
            || $user->can('settings.manage');
    }
}
