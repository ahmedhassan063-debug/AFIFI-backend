<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function viewAny(?User $user = null): bool
    {
        return true;
    }

    public function view(?User $user, Campaign $campaign): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('campaigns.manage');
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->can('campaigns.manage');
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->can('campaigns.manage');
    }

    public function manageProducts(User $user, Campaign $campaign): bool
    {
        return $user->can('campaigns.manage');
    }
}
