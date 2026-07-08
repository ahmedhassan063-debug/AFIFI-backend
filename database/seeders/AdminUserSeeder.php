<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the initial admin user.
     */
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD') ?: 'ChangeMe123!';

        $admin = User::updateOrCreate(
            ['phone' => env('ADMIN_PHONE', '01000000000')],
            [
                'name' => env('ADMIN_NAME', 'AFIFI Admin'),
                'email' => env('ADMIN_EMAIL', 'admin@afifi.local'),
                'password' => $password,
                'is_active' => true,
            ],
        );

        $admin->assignRole('super_admin');
    }
}
