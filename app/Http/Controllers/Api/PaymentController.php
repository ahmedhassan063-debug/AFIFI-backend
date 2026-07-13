<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRefundRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Requests\UpdateRefundRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\RefundResource;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\PaymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->with(['order', 'refunds'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('provider'), fn ($query) => $query->where('provider', $request->string('provider')->toString()))
            ->latest()
            ->paginate($this->perPage($request));

        return PaymentResource::collection($payments);
    }

    public function show(Payment $payment): PaymentResource
    {
        $this->authorize('view', $payment);

        return new PaymentResource($payment->load(['order', 'refunds']));
    }

    public function markAsPaid(Request $request, Payment $payment): PaymentResource
    {
        $this->authorize('markAsPaid', $payment);
        $data = $request->validate([
            'provider_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'paid_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $payment = $this->paymentService->markPaymentAsPaid(
            $payment,
            $data['provider_reference'] ?? null,
            $data['paid_at'] ?? null
        );

        return new PaymentResource($payment->load(['order', 'refunds']));
    }

    public function updateStatus(UpdatePaymentRequest $request, Payment $payment): PaymentResource
    {
        $this->authorize('update', $payment);
        $data = $request->validated();

        $payment = $this->paymentService->updatePaymentStatus(
            $payment,
            $data['status'] ?? $payment->status,
            $data
        );

        return new PaymentResource($payment->load(['order', 'refunds']));
    }

    public function refund(StoreRefundRequest $request, Payment $payment): JsonResponse
    {
        $this->authorize('refund', $payment);
        $data = $request->validated();

        abort_unless((int) $data['payment_id'] === $payment->id, 403);
        abort_unless((int) $data['order_id'] === $payment->order_id, 403);

        $refund = $this->paymentService->createRefund($payment, $data);

        return (new RefundResource($refund->load(['payment', 'order'])))
            ->response()
            ->setStatusCode(201);
    }

    public function updateRefundStatus(UpdateRefundRequest $request, Payment $payment, Refund $refund): RefundResource
    {
        $this->authorize('refund', $payment);
        $data = $request->validated();

        abort_unless((int) $refund->payment_id === $payment->id, 403);

        if (array_key_exists('payment_id', $data)) {
            abort_unless((int) $data['payment_id'] === $payment->id, 403);
        }

        if (array_key_exists('order_id', $data)) {
            abort_unless((int) $data['order_id'] === $payment->order_id, 403);
        }

        $refund = $this->paymentService->updateRefundStatus(
            $refund,
            $data['status'] ?? $refund->status,
            $data['processed_at'] ?? null
        );

        return new RefundResource($refund->load(['payment', 'order']));
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
