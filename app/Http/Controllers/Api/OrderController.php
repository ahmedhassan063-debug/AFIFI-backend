<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitPaymentReferenceRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly PaymentService $paymentService,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = auth()->user()
            ->orders()
            ->with(['items', 'addresses', 'shipment', 'payments'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->string('payment_status')->toString()))
            ->latest()
            ->paginate($this->perPage($request));

        return OrderResource::collection($orders);
    }

    public function show(Order $order): OrderResource
    {
        $this->ensureOrderBelongsToUser($order);
        $this->authorize('view', $order);

        return new OrderResource($order->load([
            'addresses',
            'items',
            'statusHistory',
            'shipment',
            'payments',
            'refunds',
            'couponRedemption',
            'returnRequests',
        ]));
    }

    /**
     * Admin-facing order list. Unlike index(), this is not scoped to the
     * authenticated user - access is controlled entirely by the
     * `orders.view` permission (route middleware + OrderPolicy::viewAny).
     */
    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::query()
            ->with(['user', 'items', 'addresses', 'shipment', 'payments'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->string('payment_status')->toString()))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhere('guest_email', 'like', "%{$search}%")
                        ->orWhere('guest_phone', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($this->perPage($request));

        return OrderResource::collection($orders);
    }

    /**
     * Admin-facing order detail. Unlike show(), this does not require the
     * order to belong to the authenticated user.
     */
    public function adminShow(Order $order): OrderResource
    {
        $this->authorize('view', $order);

        return new OrderResource($order->load([
            'user',
            'addresses',
            'items',
            'statusHistory',
            'shipment',
            'payments',
            'refunds',
            'couponRedemption',
            'returnRequests',
        ]));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): OrderResource
    {
        $this->authorize('updateStatus', $order);
        $data = $request->validated();

        $order = $this->orderService->updateOrderStatus(
            $order,
            $data['status'],
            $data['admin_notes'] ?? null,
            auth()->user()->id
        );

        return new OrderResource($order->load(['statusHistory']));
    }

    public function submitPaymentReference(SubmitPaymentReferenceRequest $request, Order $order): JsonResponse
    {
        $this->ensureOrderBelongsToUser($order);
        $this->authorize('submitPaymentReference', $order);

        $payment = $this->paymentService->submitProviderReference(
            $order,
            $request->validated('provider_reference')
        );

        return response()->json([
            'message' => 'Payment reference submitted successfully.',
            'payment' => new PaymentResource($payment),
        ]);
    }

    public function cancel(Order $order): JsonResponse
    {
        $this->ensureOrderBelongsToUser($order);
        $this->authorize('cancel', $order);

        $order = $this->orderService->updateOrderStatus(
            $order,
            'cancelled',
            'Cancelled by customer.',
            auth()->user()->id
        );

        return response()->json([
            'message' => 'Order cancelled successfully.',
            'order' => new OrderResource($order),
        ]);
    }

    private function ensureOrderBelongsToUser(Order $order): void
    {
        abort_unless($order->user_id === auth()->user()->id, 404);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
