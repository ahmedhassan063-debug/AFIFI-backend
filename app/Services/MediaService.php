<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
        return (bool) $this->resolveMedia($media)->delete();
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
            return trim(str_replace('\\', '/', $path), '/');
        }

        $directory = $this->normalizeDirectory($directory);
        $filename = $filename !== null ? $this->normalizeFilename($filename) : '';

        return trim($directory.'/'.$filename, '/');
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
            }

            if (isset($prepared['filename'])) {
                $prepared['path'] = $this->normalizePath($prepared['directory'], $prepared['filename']);
            }
        }

        return $prepared;
    }

    private function resolveMedia(Media|int $media): Media
    {
        if ($media instanceof Media) {
            return $media;
        }

        return Media::query()->findOrFail($media);
    }
}
