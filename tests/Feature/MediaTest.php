<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MediaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');
    }

    private function marketingUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('marketing');

        return $user;
    }

    private function validMediaPayload(array $overrides = []): array
    {
        return array_merge([
            'directory' => 'products/test',
            'filename' => 'image.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 2048,
            'width' => 800,
            'height' => 600,
        ], $overrides);
    }

    public function test_media_store_rejects_path_traversal(): void
    {
        Sanctum::actingAs($this->marketingUser());

        $response = $this->postJson('/api/admin/media', $this->validMediaPayload([
            'path' => '../outside/image.jpg',
        ]));

        $response->assertUnprocessable();
        $this->assertDatabaseCount('media', 0);
    }

    public function test_media_store_rejects_executable_mime_type(): void
    {
        Sanctum::actingAs($this->marketingUser());

        $response = $this->postJson('/api/admin/media', $this->validMediaPayload([
            'filename' => 'script.php',
            'mime_type' => 'application/x-php',
        ]));

        $response->assertUnprocessable();
    }

    public function test_media_store_returns_public_url_not_absolute_filesystem_path(): void
    {
        Sanctum::actingAs($this->marketingUser());

        $response = $this->postJson('/api/admin/media', $this->validMediaPayload());

        $response->assertCreated();
        $response->assertJsonPath('data.path', 'products/test/image.jpg');
        $response->assertJsonPath('data.url', Storage::disk('public')->url('products/test/image.jpg'));
        $this->assertStringNotContainsString(storage_path(), (string) $response->json('data.url'));
    }

    public function test_media_delete_soft_deletes_record_and_handles_missing_file(): void
    {
        Sanctum::actingAs($this->marketingUser());

        $createResponse = $this->postJson('/api/admin/media', $this->validMediaPayload([
            'filename' => 'delete-me.jpg',
        ]))->assertCreated();

        $mediaId = $createResponse->json('data.id');

        $this->deleteJson("/api/admin/media/{$mediaId}")
            ->assertOk()
            ->assertJsonPath('message', 'Media deleted successfully.');

        $this->assertSoftDeleted('media', ['id' => $mediaId]);

        $orphan = Media::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'disk' => 'public',
            'directory' => 'products/test',
            'filename' => 'missing.jpg',
            'path' => 'products/test/missing.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'uploaded_by' => auth()->id(),
        ]);

        $this->deleteJson("/api/admin/media/{$orphan->id}")->assertOk();
    }

    public function test_media_update_does_not_allow_uploaded_by_reassignment(): void
    {
        $owner = $this->marketingUser();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($owner);

        $createResponse = $this->postJson('/api/admin/media', $this->validMediaPayload())->assertCreated();
        $mediaId = $createResponse->json('data.id');

        Sanctum::actingAs($otherUser);
        $otherUser->givePermissionTo('cms.manage');

        $response = $this->patchJson("/api/admin/media/{$mediaId}", [
            'alt_text' => 'Updated alt text',
            'uploaded_by' => $otherUser->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.uploaded_by', $owner->id);
        $response->assertJsonPath('data.alt_text', 'Updated alt text');
        $this->assertDatabaseHas('media', [
            'id' => $mediaId,
            'uploaded_by' => $owner->id,
            'alt_text' => 'Updated alt text',
        ]);
    }
}
