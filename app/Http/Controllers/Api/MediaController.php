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

        $media = $this->mediaService->createMetadata(array_merge($this->validatedMedia($request), [
            'uploaded_by' => auth()->user()?->id,
        ]));

        return (new MediaResource($media->load('uploadedBy')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Media $media): MediaResource
    {
        $this->authorize('view', $media);

        return new MediaResource($media->load('uploadedBy'));
    }

    public function update(Request $request, Media $media): MediaResource
    {
        $this->authorize('update', $media);

        $media = $this->mediaService->updateMetadata($media, $this->validatedMedia($request, false));

        return new MediaResource($media->load('uploadedBy'));
    }

    public function destroy(Media $media): JsonResponse
    {
        $this->authorize('delete', $media);

        $this->mediaService->softDelete($media);

        return response()->json([
            'message' => 'Media deleted successfully.',
        ]);
    }

    private function validatedMedia(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'uuid' => ['sometimes', 'string', 'max:36'],
            'disk' => ['sometimes', 'string', 'max:50'],
            'directory' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'filename' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'path' => ['sometimes', 'string', 'max:500'],
            'mime_type' => [$creating ? 'required' : 'sometimes', 'string', 'max:100'],
            'size_bytes' => [$creating ? 'required' : 'sometimes', 'integer', 'min:0'],
            'width' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'height' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'alt_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'uploaded_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ]);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
