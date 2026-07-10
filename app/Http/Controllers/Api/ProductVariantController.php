<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductVariantRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\ProductVariant;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductVariantController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProductVariant::class);

        $sort = $request->string('sort', 'created_at')->toString();
        $direction = $request->string('direction', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['sku', 'barcode', 'stock', 'price_override', 'created_at'];

        $variants = ProductVariant::query()
            ->with(['product', 'color', 'size'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', $request->integer('product_id')))
            ->when($request->filled('color_id'), fn ($query) => $query->where('color_id', $request->integer('color_id')))
            ->when($request->filled('size_id'), fn ($query) => $query->where('size_id', $request->integer('size_id')))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('in_stock'), fn ($query) => $request->boolean('in_stock')
                ? $query->where('stock', '>', 0)
                : $query->where('stock', 0))
            ->when($request->routeIs('catalog.*'), function ($query) {
                $query->where('is_active', true)
                    ->whereHas('product', fn ($productQuery) => $productQuery->where('is_active', true));
            })
            ->orderBy(in_array($sort, $allowedSorts, true) ? $sort : 'created_at', $direction)
            ->paginate($this->perPage($request));

        return ProductVariantResource::collection($variants);
    }

    public function store(StoreProductVariantRequest $request): JsonResponse
    {
        $this->authorize('create', ProductVariant::class);

        $variant = ProductVariant::query()->create($request->validated());

        return (new ProductVariantResource($variant->load(['product', 'color', 'size'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, ProductVariant $productVariant): ProductVariantResource
    {
        $this->authorize('view', $productVariant);

        if ($request->routeIs('catalog.*') && (! $productVariant->is_active || ! $productVariant->product?->is_active)) {
            abort(404);
        }

        return new ProductVariantResource($productVariant->load(['product', 'color', 'size']));
    }

    public function update(UpdateProductVariantRequest $request, ProductVariant $productVariant): ProductVariantResource
    {
        $this->authorize('update', $productVariant);

        $productVariant->update($request->validated());

        return new ProductVariantResource($productVariant->refresh()->load(['product', 'color', 'size']));
    }

    public function destroy(ProductVariant $productVariant): JsonResponse
    {
        $this->authorize('delete', $productVariant);

        $productVariant->delete();

        return response()->json([
            'message' => 'Product variant deleted successfully.',
        ]);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
