<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCouponRequest;
use App\Http\Requests\UpdateCouponRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CouponController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly CouponService $couponService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Coupon::class);

        $coupons = Coupon::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where('code', 'like', "%{$search}%");
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->paginate($this->perPage($request));

        return CouponResource::collection($coupons);
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $this->authorize('create', Coupon::class);

        $coupon = Coupon::query()->create($request->validated());

        return (new CouponResource($coupon))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Coupon $coupon): CouponResource
    {
        $this->authorize('view', $coupon);

        return new CouponResource($coupon);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): CouponResource
    {
        $this->authorize('update', $coupon);

        $coupon->update($request->validated());

        return new CouponResource($coupon->refresh());
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $this->authorize('delete', $coupon);

        $coupon->delete();

        return response()->json([
            'message' => 'Coupon deleted successfully.',
        ]);
    }

    public function calculateDiscount(Request $request, Coupon $coupon): JsonResponse
    {
        $this->authorize('view', $coupon);

        $data = $request->validate([
            'order_total' => ['required', 'numeric', 'min:0'],
        ]);

        return response()->json([
            'discount' => $this->couponService->calculateDiscount($coupon, (float) $data['order_total']),
        ]);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
