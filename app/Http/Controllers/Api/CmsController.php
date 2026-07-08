<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AboutContentResource;
use App\Http\Resources\AboutStatResource;
use App\Http\Resources\AboutValueResource;
use App\Http\Resources\BannerResource;
use App\Http\Resources\FaqItemResource;
use App\Http\Resources\HomepageSectionResource;
use App\Http\Resources\PolicyPageResource;
use App\Http\Resources\TrustStripItemResource;
use App\Services\CmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsController extends Controller
{
    public function __construct(private readonly CmsService $cmsService)
    {
    }

    public function homepage(): JsonResponse
    {
        return response()->json([
            'sections' => HomepageSectionResource::collection($this->cmsService->getActiveHomepageSections()),
            'trust_strip_items' => TrustStripItemResource::collection($this->cmsService->getTrustStripItems()),
            'banners' => BannerResource::collection($this->cmsService->getActiveBannersByPlacement()),
        ]);
    }

    public function banners(Request $request): JsonResponse
    {
        return response()->json([
            'banners' => BannerResource::collection(
                $this->cmsService->getActiveBannersByPlacement($request->string('placement', 'hero')->toString())
            ),
        ]);
    }

    public function about(): JsonResponse
    {
        $about = $this->cmsService->getAboutPageData();

        return response()->json([
            'content' => new AboutContentResource($about['content']),
            'values' => AboutValueResource::collection($about['values']),
            'stats' => AboutStatResource::collection($about['stats']),
        ]);
    }

    public function faq(): JsonResponse
    {
        return response()->json([
            'items' => FaqItemResource::collection($this->cmsService->getFaqItems()),
        ]);
    }

    public function policy(string $slug): PolicyPageResource
    {
        return new PolicyPageResource($this->cmsService->getPolicyPageBySlug($slug));
    }
}
