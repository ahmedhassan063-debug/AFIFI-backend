<?php

namespace Tests\Unit;

use App\Models\Media;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class MediaServiceTest extends TestCase
{
    public function test_sanitize_storage_path_rejects_parent_directory_segments(): void
    {
        $service = app(MediaService::class);

        $this->expectException(RuntimeException::class);

        $service->sanitizeStoragePath('products/../secret.jpg');
    }

    public function test_soft_delete_removes_existing_storage_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/test/file.jpg', 'binary');

        $media = Media::query()->create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'public',
            'directory' => 'products/test',
            'filename' => 'file.jpg',
            'path' => 'products/test/file.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'uploaded_by' => User::factory()->create()->id,
        ]);

        app(MediaService::class)->softDelete($media);

        Storage::disk('public')->assertMissing('products/test/file.jpg');
        $this->assertSoftDeleted('media', ['id' => $media->id]);
    }
}
