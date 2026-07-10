<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_public_settings_only_expose_is_public_keys(): void
    {
        Setting::query()->create([
            'key' => 'site.name',
            'value' => 'AFIFI',
            'type' => 'string',
            'group' => 'site',
            'is_public' => true,
        ]);

        Setting::query()->create([
            'key' => 'checkout.max_cart_quantity',
            'value' => '10',
            'type' => 'integer',
            'group' => 'checkout',
            'is_public' => false,
        ]);

        $response = $this->getJson('/api/settings/public');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.key', 'site.name');
    }

    public function test_customer_cannot_access_admin_settings_index(): void
    {
        $customer = User::factory()->create();
        Sanctum::actingAs($customer);

        $this->getJson('/api/admin/settings')->assertForbidden();
    }

    public function test_admin_with_settings_permission_can_access_all_settings(): void
    {
        Setting::query()->create([
            'key' => 'checkout.max_cart_quantity',
            'value' => '10',
            'type' => 'integer',
            'group' => 'checkout',
            'is_public' => false,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/settings');

        $response->assertOk();
        $response->assertJsonPath('data.0.key', 'checkout.max_cart_quantity');
    }
}
