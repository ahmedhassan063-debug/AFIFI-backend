<?php

namespace Tests\Feature;

use App\Models\AdminPreference;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPreferenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_store_preference_for_self_only(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/admin-preferences', [
            'user_id' => $admin->id,
            'key' => 'dashboard.range',
            'value' => '30d',
            'type' => 'string',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.key', 'dashboard.range');
        $this->assertDatabaseHas('admin_preferences', [
            'user_id' => $admin->id,
            'key' => 'dashboard.range',
            'value' => '30d',
        ]);
    }

    public function test_admin_cannot_store_preference_for_another_user(): void
    {
        $admin = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin->assignRole('super_admin');
        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/admin-preferences', [
            'user_id' => $otherUser->id,
            'key' => 'dashboard.range',
            'value' => '30d',
            'type' => 'string',
        ])->assertForbidden();
    }

    public function test_admin_can_update_preference_by_route_key(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        Sanctum::actingAs($admin);

        AdminPreference::query()->create([
            'user_id' => $admin->id,
            'key' => 'dashboard.range',
            'value' => '7d',
            'type' => 'string',
        ]);

        $response = $this->putJson('/api/admin/admin-preferences/dashboard.range', [
            'value' => '90d',
            'type' => 'string',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.value', '90d');
        $this->assertDatabaseHas('admin_preferences', [
            'user_id' => $admin->id,
            'key' => 'dashboard.range',
            'value' => '90d',
        ]);
    }
}
