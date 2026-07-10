<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_customer_cannot_access_admin_contact_messages(): void
    {
        $message = ContactMessage::query()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'message' => 'Need help with my order.',
            'status' => 'new',
        ]);

        $customer = User::factory()->create();
        Sanctum::actingAs($customer);

        $this->getJson('/api/admin/contact-messages')->assertForbidden();
        $this->getJson("/api/admin/contact-messages/{$message->id}")->assertForbidden();
        $this->patchJson("/api/admin/contact-messages/{$message->id}/status", [
            'status' => 'read',
        ])->assertForbidden();
        $this->deleteJson("/api/admin/contact-messages/{$message->id}")->assertForbidden();
    }

    public function test_support_user_can_view_and_update_contact_messages(): void
    {
        $message = ContactMessage::query()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'message' => 'Need help with my order.',
            'status' => 'new',
        ]);

        $support = User::factory()->create();
        $support->assignRole('support');
        Sanctum::actingAs($support);

        $this->getJson('/api/admin/contact-messages')->assertOk();
        $this->getJson("/api/admin/contact-messages/{$message->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'new');

        $this->patchJson("/api/admin/contact-messages/{$message->id}/status", [
            'status' => 'read',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'read');
    }
}
