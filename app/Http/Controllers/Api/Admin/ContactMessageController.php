<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateContactMessageStatusRequest;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactMessageController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ContactMessage::class);

        $messages = ContactMessage::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate($this->perPage($request));

        return ContactMessageResource::collection($messages);
    }

    public function show(ContactMessage $contactMessage): ContactMessageResource
    {
        $this->authorize('view', $contactMessage);

        return new ContactMessageResource($contactMessage);
    }

    public function updateStatus(UpdateContactMessageStatusRequest $request, ContactMessage $contactMessage): ContactMessageResource
    {
        $this->authorize('updateStatus', $contactMessage);

        $contactMessage->update($request->validated());

        return new ContactMessageResource($contactMessage->refresh());
    }

    public function destroy(ContactMessage $contactMessage): JsonResponse
    {
        $this->authorize('delete', $contactMessage);

        $contactMessage->delete();

        return response()->json([
            'message' => 'Contact message deleted successfully.',
        ]);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
