<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_customer_cannot_access_admin_routes(): void
    {
        $customer = User::factory()->create();
        Sanctum::actingAs($customer);

        $response = $this->getJson('/api/admin/products');

        $response->assertStatus(403);
    }

    public function test_admin_with_permission_can_access_protected_route(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('catalog_manager');
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/products');

        $response->assertOk();
    }

    public function test_authenticated_user_without_specific_permission_gets_403(): void
    {
        $support = User::factory()->create();
        $support->assignRole('support');
        Sanctum::actingAs($support);

        $response = $this->postJson('/api/admin/products', []);

        $response->assertStatus(403);
    }

    public function test_guest_gets_401_on_admin_route(): void
    {
        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(401);
    }

    public function test_policy_protected_resource_rejects_non_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $address = Address::factory()->for($owner)->create();

        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/addresses/{$address->id}");

        $response->assertStatus(404);
    }
}
