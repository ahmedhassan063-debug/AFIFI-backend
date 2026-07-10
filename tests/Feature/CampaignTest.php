<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_active_campaigns_endpoint_hides_inactive_expired_and_future_campaigns(): void
    {
        Campaign::query()->create([
            'name' => 'Live Sale',
            'slug' => 'live-sale',
            'type' => 'seasonal',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        Campaign::query()->create([
            'name' => 'Inactive Sale',
            'slug' => 'inactive-sale',
            'type' => 'seasonal',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => false,
        ]);

        Campaign::query()->create([
            'name' => 'Expired Sale',
            'slug' => 'expired-sale',
            'type' => 'seasonal',
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
            'is_active' => true,
        ]);

        Campaign::query()->create([
            'name' => 'Future Sale',
            'slug' => 'future-sale',
            'type' => 'seasonal',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(10),
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/campaigns/active');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.slug', 'live-sale');
    }

    public function test_campaign_update_rejects_invalid_date_range_when_only_ends_at_changes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('marketing');
        Sanctum::actingAs($admin);

        $campaign = Campaign::query()->create([
            'name' => 'Spring Sale',
            'slug' => 'spring-sale',
            'type' => 'seasonal',
            'starts_at' => now()->addDays(5),
            'ends_at' => now()->addDays(10),
            'is_active' => true,
        ]);

        $response = $this->patchJson("/api/admin/campaigns/{$campaign->id}", [
            'ends_at' => now()->addDay()->toDateTimeString(),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['ends_at']);
    }
}
