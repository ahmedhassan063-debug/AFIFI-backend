<?php

namespace App\Providers;

use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Role is a package model (Spatie\Permission\Models\Role), not under
        // App\Models, so Laravel's App\Models\* => App\Policies\*Policy
        // auto-discovery convention won't find RolePolicy automatically.
        Gate::policy(Role::class, RolePolicy::class);
    }
}
