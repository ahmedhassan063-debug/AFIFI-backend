<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        $permissions = [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'inventory.view',
            'inventory.update',
            'orders.view',
            'orders.update',
            'payments.view',
            'coupons.manage',
            'campaigns.manage',
            'cms.manage',
            'settings.manage',
            'reports.view',
            'roles.view',
            'roles.manage',
            'contact.view',
            'contact.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        $roles = [
            'super_admin' => $permissions,
            'catalog_manager' => [
                'products.view',
                'products.create',
                'products.update',
                'products.delete',
                'inventory.view',
                'inventory.update',
            ],
            'fulfillment' => [
                'inventory.view',
                'inventory.update',
                'orders.view',
                'orders.update',
                'payments.view',
            ],
            'support' => [
                'users.view',
                'orders.view',
                'orders.update',
                'payments.view',
                'contact.view',
                'contact.manage',
            ],
            'marketing' => [
                'products.view',
                'coupons.manage',
                'campaigns.manage',
                'cms.manage',
                'reports.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guard,
            ]);

            $role->syncPermissions($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
