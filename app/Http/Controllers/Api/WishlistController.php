<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWishlistItemRequest;
use App\Http\Resources\WishlistItemResource;
use App\Http\Resources\WishlistResource;
use App\Models\WishlistItem;
use App\Services\WishlistService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class WishlistController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly WishlistService $wishlistService)
    {
    }

    public function index(): WishlistResource
    {
        $wishlist = $this->wishlistService->getOrCreateWishlistForUser(auth()->user()->id);
        $this->authorize('view', $wishlist);

        return new WishlistResource($wishlist->load(['items.product', 'items.productVariant']));
    }

    public function store(StoreWishlistItemRequest $request): WishlistItemResource
    {
        $wishlist = $this->wishlistService->getOrCreateWishlistForUser(auth()->user()->id);
        $this->authorize('view', $wishlist);
        $this->authorize('create', WishlistItem::class);
        $data = $request->validated();

        $item = $this->wishlistService->addItem(
            auth()->user()->id,
            $data['product_id'],
            $data['product_variant_id'] ?? null
        );

        return new WishlistItemResource($item->load(['product', 'productVariant']));
    }

    public function destroy(WishlistItem $wishlistItem): JsonResponse
    {
        $this->authorize('delete', $wishlistItem);
        $this->wishlistService->removeItem(auth()->user()->id, $wishlistItem->id);

        return response()->json([
            'message' => 'Wishlist item removed successfully.',
        ]);
    }
}
