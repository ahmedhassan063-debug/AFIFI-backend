<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\CatalogService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly CatalogService $catalogService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        $sort = $request->string('sort', 'sort_order')->toString();
        $direction = $request->string('direction', 'asc')->toString() === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['name', 'slug', 'base_price', 'sort_order', 'published_at', 'created_at'];

        $products = Product::query()
            ->with(['brand', 'category', 'variants.color', 'variants.size', 'images.media', 'tags', 'collections'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('brand_id'), fn ($query) => $query->where('brand_id', $request->integer('brand_id')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('gender'), fn ($query) => $query->where('gender', $request->string('gender')->toString()))
            ->when($request->filled('badge'), fn ($query) => $query->where('badge', $request->string('badge')->toString()))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('is_new_arrival'), fn ($query) => $query->where('is_new_arrival', $request->boolean('is_new_arrival')))
            ->when($request->filled('is_best_seller'), fn ($query) => $query->where('is_best_seller', $request->boolean('is_best_seller')))
            ->when($request->filled('is_featured_drop'), fn ($query) => $query->where('is_featured_drop', $request->boolean('is_featured_drop')))
            ->when($request->filled('min_price'), fn ($query) => $query->where('base_price', '>=', $request->input('min_price')))
            ->when($request->filled('max_price'), fn ($query) => $query->where('base_price', '<=', $request->input('max_price')))
            ->when($request->routeIs('catalog.*'), fn ($query) => $query->where('is_active', true))
            ->orderBy(in_array($sort, $allowedSorts, true) ? $sort : 'sort_order', $direction)
            ->paginate($this->perPage($request));

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = $this->catalogService->createProduct($request->validated());

        return (new ProductResource($product->load(['brand', 'category', 'variants.color', 'variants.size', 'images.media', 'tags', 'collections'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Product $product): ProductResource
    {
        $this->authorize('view', $product);

        if ($request->routeIs('catalog.*') && ! $product->is_active) {
            abort(404);
        }

        return new ProductResource($product->load(['brand', 'category', 'variants.color', 'variants.size', 'images.media', 'tags', 'collections']));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $this->authorize('update', $product);

        $product = $this->catalogService->updateProduct($product, $request->validated());

        return new ProductResource($product->load(['brand', 'category', 'variants.color', 'variants.size', 'images.media', 'tags', 'collections']));
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
