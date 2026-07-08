<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReturnRequestRequest;
use App\Http\Requests\UpdateReturnRequestRequest;
use App\Http\Resources\ReturnRequestResource;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Services\OrderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReturnRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly OrderService $orderService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $returnRequests = ReturnRequest::query()
            ->whereHas('order', fn ($query) => $query->where('user_id', auth()->user()->id))
            ->with(['order', 'orderItem'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->latest()
            ->paginate($this->perPage($request));

        return ReturnRequestResource::collection($returnRequests);
    }

    public function show(ReturnRequest $returnRequest): ReturnRequestResource
    {
        $this->ensureReturnRequestBelongsToUser($returnRequest);
        $this->authorize('view', $returnRequest);

        return new ReturnRequestResource($returnRequest->load(['order', 'orderItem']));
    }

    public function store(StoreReturnRequestRequest $request): ReturnRequestResource
    {
        $this->authorize('create', ReturnRequest::class);
        $data = $request->validated();
        $order = Order::query()->findOrFail($data['order_id']);

        $this->ensureOrderBelongsToUser($order);
        abort_unless($order->items()->whereKey($data['order_item_id'])->exists(), 403);

        $returnRequest = $this->orderService->createReturnRequest($order, $data);

        return new ReturnRequestResource($returnRequest->load(['order', 'orderItem']));
    }

    public function updateStatus(UpdateReturnRequestRequest $request, ReturnRequest $returnRequest): ReturnRequestResource
    {
        $this->authorize('updateStatus', $returnRequest);
        $data = $request->validated();

        $returnRequest = $this->orderService->updateReturnRequestStatus(
            $returnRequest,
            $data['status'] ?? $returnRequest->status,
            $data['admin_notes'] ?? null,
            $data['resolved_at'] ?? null
        );

        return new ReturnRequestResource($returnRequest->load(['order', 'orderItem']));
    }

    private function ensureOrderBelongsToUser(Order $order): void
    {
        abort_unless($order->user_id === auth()->user()->id, 404);
    }

    private function ensureReturnRequestBelongsToUser(ReturnRequest $returnRequest): void
    {
        abort_unless($returnRequest->order?->user_id === auth()->user()->id, 404);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
