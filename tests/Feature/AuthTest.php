<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '01012345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['message', 'user', 'token']);
        $response->assertJsonStructure(['user' => ['id', 'name', 'email', 'phone', 'created_at']]);
        $response->assertJsonMissingPath('user.password');
        $response->assertJsonMissingPath('user.is_active');
        $this->assertDatabaseHas('users', [
            'phone' => '01012345678',
            'email' => 'john@example.com',
        ]);
    }

    public function test_login_returns_token_for_valid_credentials(): void
    {
        User::factory()->create(['phone' => '01000000001']);

        $response = $this->postJson('/api/auth/login', [
            'login' => '01000000001',
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'user', 'token']);
        $response->assertJsonMissingPath('user.password');
        $response->assertJsonMissingPath('user.is_active');
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/me');

        $response->assertOk();
        $response->assertJsonPath('user.id', $user->id);
        $response->assertJsonMissingPath('user.password');
        $response->assertJsonMissingPath('user.is_active');
        $response->assertJsonMissingPath('user.roles');
    }

    public function test_logout_revokes_token(): void
    {
        User::factory()->create(['phone' => '01000000002']);

        $login = $this->postJson('/api/auth/login', [
            'login' => '01000000002',
            'password' => 'password',
        ]);
        $token = $login->json('token');

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        // Sanctum's guard caches the resolved user for the request lifecycle;
        // forget it so the next call re-validates the (now deleted) token.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_update_profile_updates_user_details(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/profile', [
            'name' => 'Updated Name',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_change_password_updates_password(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_unauthenticated_access_returns_401(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }
}
