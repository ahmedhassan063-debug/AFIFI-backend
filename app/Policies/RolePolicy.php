<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.view');
    }

    /**
     * The super_admin role's permission set is the platform's access ceiling
     * and must stay immutable via the API - changing it could lock every
     * admin out of critical areas or silently grant it unintended access.
     */
    public function update(User $user, Role $role): bool
    {
        return $role->name !== 'super_admin' && $user->can('roles.manage');
    }
}
