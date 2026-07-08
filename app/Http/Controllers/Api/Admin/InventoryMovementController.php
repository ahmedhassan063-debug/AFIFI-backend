<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryAdjustmentRequest;
use App\Http\Resources\InventoryMovementResource;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryMovementController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    /**
     * Admin-facing inventory movement (audit) log. Gated entirely by the
     * `inventory.view` permission via route middleware.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $movements = InventoryMovement::query()
            ->with(['productVariant.product', 'productVariant.color', 'productVariant.size', 'createdBy'])
            ->when($request->filled('product_variant_id'), fn ($query) => $query->where('product_variant_id', $request->integer('product_variant_id')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->latest()
            ->paginate($this->perPage($request));

        return InventoryMovementResource::collection($movements);
    }

    /**
     * Records a manual stock adjustment through the existing InventoryService
     * (delta-based, transactional, cannot drive stock negative) instead of
     * letting the client set ProductVariant.stock directly.
     */
    public function store(StoreInventoryAdjustmentRequest $request, ProductVariant $productVariant): JsonResponse
    {
        $this->authorize('updateInventory', $productVariant);

        $movement = $this->inventoryService->adjustStock(
            $productVariant,
            (int) $request->validated('quantity_delta'),
            $request->validated('notes'),
            $request->user()?->id
        );

        return (new InventoryMovementResource(
            $movement->load(['productVariant.product', 'productVariant.color', 'productVariant.size', 'createdBy'])
        ))->response()->setStatusCode(201);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
