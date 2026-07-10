<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MediaController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly MediaService $mediaService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Media::class);

        $media = Media::query()
            ->with('uploadedBy')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('filename', 'like', "%{$search}%")
                        ->orWhere('path', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('mime_type'), fn ($query) => $query->where('mime_type', $request->string('mime_type')->toString()))
            ->latest()
            ->paginate($this->perPage($request));

        return MediaResource::collection($media);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Media::class);

        try {
            $media = $this->mediaService->createMetadata(array_merge($this->validatedMedia($request), [
                'uploaded_by' => auth()->user()?->id,
            ]));
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages([
                'media' => [$exception->getMessage()],
            ]);
        }

        return (new MediaResource($media->load('uploadedBy')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Media $medium): MediaResource
    {
        $this->authorize('view', $medium);

        return new MediaResource($medium->load('uploadedBy'));
    }

    public function update(Request $request, Media $medium): MediaResource
    {
        $this->authorize('update', $medium);

        try {
            $medium = $this->mediaService->updateMetadata($medium, $this->validatedMedia($request, false));
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages([
                'media' => [$exception->getMessage()],
            ]);
        }

        return new MediaResource($medium->load('uploadedBy'));
    }

    public function destroy(Media $medium): JsonResponse
    {
        $this->authorize('delete', $medium);

        $this->mediaService->softDelete($medium);

        return response()->json([
            'message' => 'Media deleted successfully.',
        ]);
    }

    private function validatedMedia(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'uuid' => ['sometimes', 'string', 'max:36'],
            'disk' => ['sometimes', 'string', Rule::in(config('media.allowed_disks', ['public', 'local']))],
            'directory' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'filename' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'path' => ['sometimes', 'string', 'max:500'],
            'mime_type' => [$creating ? 'required' : 'sometimes', 'string', Rule::in(config('media.allowed_mime_types', []))],
            'size_bytes' => [$creating ? 'required' : 'sometimes', 'integer', 'min:1', 'max:'.config('media.max_size_bytes', 10 * 1024 * 1024)],
            'width' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:20000'],
            'height' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:20000'],
            'alt_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
