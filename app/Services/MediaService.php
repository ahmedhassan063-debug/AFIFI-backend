<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MediaService
{
    public function createMetadata(array $data): Media
    {
        return Media::query()->create($this->prepareMetadata($data));
    }

    public function updateMetadata(Media|int $media, array $data): Media
    {
        $media = $this->resolveMedia($media);

        $media->update($this->prepareMetadata($data, false));

        return $media->refresh();
    }

    public function softDelete(Media|int $media): bool
    {
        $media = $this->resolveMedia($media);
        $this->deleteStoredFile($media);

        return (bool) $media->delete();
    }

    public function resolvePublicUrl(Media $media): ?string
    {
        if ($media->disk !== 'public' || $media->path === '') {
            return null;
        }

        try {
            $path = $this->sanitizeStoragePath($media->path);
        } catch (RuntimeException) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function normalizeDirectory(?string $directory): string
    {
        if ($directory === null) {
            return '';
        }

        return trim(str_replace('\\', '/', $directory), '/');
    }

    public function normalizeFilename(string $filename): string
    {
        return basename(str_replace('\\', '/', $filename));
    }

    public function normalizePath(?string $directory, ?string $filename, ?string $path = null): string
    {
        if ($path !== null && $path !== '') {
            return $this->sanitizeStoragePath($path);
        }

        $directory = $this->normalizeDirectory($directory);
        $filename = $filename !== null ? $this->normalizeFilename($filename) : '';

        return $this->sanitizeStoragePath(trim($directory.'/'.$filename, '/'));
    }

    public function assertAllowedMimeType(string $mimeType): void
    {
        if (! in_array(strtolower($mimeType), $this->allowedMimeTypes(), true)) {
            throw new RuntimeException('Unsupported media mime type.');
        }
    }

    public function assertAllowedFilename(string $filename): void
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($extension === '' || in_array($extension, $this->blockedExtensions(), true)) {
            throw new RuntimeException('Unsupported media file extension.');
        }
    }

    public function assertAllowedSize(int $sizeBytes): void
    {
        if ($sizeBytes < 1 || $sizeBytes > $this->maxSizeBytes()) {
            throw new RuntimeException('Media file size is outside the allowed limits.');
        }
    }

    public function assertAllowedDisk(string $disk): void
    {
        if (! in_array($disk, $this->allowedDisks(), true)) {
            throw new RuntimeException('Unsupported media storage disk.');
        }
    }

    public function sanitizeStoragePath(string $path): string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');

        if ($normalized === '' || str_starts_with($normalized, '/') || preg_match('/^[a-zA-Z]:/', $normalized)) {
            throw new RuntimeException('Invalid media storage path.');
        }

        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new RuntimeException('Invalid media storage path.');
            }
        }

        return $normalized;
    }

    private function prepareMetadata(array $data, bool $creating = true): array
    {
        $prepared = Arr::only($data, [
            'uuid',
            'disk',
            'directory',
            'filename',
            'path',
            'mime_type',
            'size_bytes',
            'width',
            'height',
            'alt_text',
            'title',
            'uploaded_by',
        ]);

        if ($creating && empty($prepared['uuid'])) {
            $prepared['uuid'] = (string) Str::uuid();
        }

        if ($creating && empty($prepared['disk'])) {
            $prepared['disk'] = 'public';
        }

        if (isset($prepared['disk'])) {
            $this->assertAllowedDisk($prepared['disk']);
        }

        if (isset($prepared['mime_type'])) {
            $this->assertAllowedMimeType($prepared['mime_type']);
        }

        if (isset($prepared['size_bytes'])) {
            $this->assertAllowedSize((int) $prepared['size_bytes']);
        }

        if (isset($prepared['filename'])) {
            $this->assertAllowedFilename($prepared['filename']);
        }

        $path = $prepared['path'] ?? null;
        $directory = $prepared['directory'] ?? null;
        $filename = $prepared['filename'] ?? null;

        if ($path !== null && $path !== '') {
            $normalizedPath = $this->normalizePath(null, null, $path);
            $prepared['path'] = $normalizedPath;

            if ($directory === null) {
                $dirname = dirname($normalizedPath);
                $prepared['directory'] = $dirname === '.' ? '' : $this->normalizeDirectory($dirname);
            }

            if ($filename === null) {
                $prepared['filename'] = $this->normalizeFilename($normalizedPath);
            }
        } elseif ($directory !== null || $filename !== null) {
            $prepared['directory'] = $this->normalizeDirectory($directory);

            if ($filename !== null) {
                $prepared['filename'] = $this->normalizeFilename($filename);
                $this->assertAllowedFilename($prepared['filename']);
            }

            if (isset($prepared['filename'])) {
                $prepared['path'] = $this->normalizePath($prepared['directory'], $prepared['filename']);
            }
        }

        return $prepared;
    }

    private function deleteStoredFile(Media $media): void
    {
        if (! in_array($media->disk, $this->allowedDisks(), true) || $media->path === '') {
            return;
        }

        try {
            $path = $this->sanitizeStoragePath($media->path);
        } catch (RuntimeException) {
            return;
        }

        $storage = Storage::disk($media->disk);

        if ($storage->exists($path)) {
            $storage->delete($path);
        }
    }

    private function allowedMimeTypes(): array
    {
        return config('media.allowed_mime_types', []);
    }

    private function allowedDisks(): array
    {
        return config('media.allowed_disks', ['public', 'local']);
    }

    private function maxSizeBytes(): int
    {
        return (int) config('media.max_size_bytes', 10 * 1024 * 1024);
    }

    private function blockedExtensions(): array
    {
        return [
            'php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'cmd', 'js', 'html', 'htm', 'svgz',
        ];
    }

    private function resolveMedia(Media|int $media): Media
    {
        if ($media instanceof Media) {
            return $media;
        }

        return Media::query()->findOrFail($media);
    }
}
