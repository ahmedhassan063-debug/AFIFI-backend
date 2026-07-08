<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CampaignController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly CampaignService $campaignService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Campaign::class);

        $campaigns = Campaign::query()
            ->with(['bannerMedia', 'campaignProducts'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->paginate($this->perPage($request));

        return CampaignResource::collection($campaigns);
    }

    public function active(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Campaign::class);

        return CampaignResource::collection($this->campaignService->getActiveCampaigns());
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $this->authorize('create', Campaign::class);

        $campaign = Campaign::query()->create($request->validated());

        return (new CampaignResource($campaign->load(['bannerMedia', 'campaignProducts'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Campaign $campaign): CampaignResource
    {
        $this->authorize('view', $campaign);

        return new CampaignResource($campaign->load(['bannerMedia', 'campaignProducts']));
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): CampaignResource
    {
        $this->authorize('update', $campaign);

        $campaign->update($request->validated());

        return new CampaignResource($campaign->refresh()->load(['bannerMedia', 'campaignProducts']));
    }

    public function destroy(Campaign $campaign): JsonResponse
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return response()->json([
            'message' => 'Campaign deleted successfully.',
        ]);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
