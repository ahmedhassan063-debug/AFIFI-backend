<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    public function createPaymentRecord(Order|int $order, array $data): Payment
    {
        $order = $this->resolveOrder($order);

        return Payment::query()->create(array_merge(
            Arr::only($data, [
                'provider',
                'provider_reference',
                'amount',
                'currency',
                'status',
                'metadata',
                'paid_at',
            ]),
            [
                'order_id' => $order->id,
                'currency' => $data['currency'] ?? $order->currency_code,
                'status' => $data['status'] ?? 'pending',
            ]
        ));
    }

    public function markPaymentAsPaid(Payment|int $payment, ?string $providerReference = null, mixed $paidAt = null): Payment
    {
        return DB::transaction(function () use ($payment, $providerReference, $paidAt) {
            $payment = $this->resolvePayment($payment);

            $payment->update([
                'status' => 'paid',
                'provider_reference' => $providerReference ?? $payment->provider_reference,
                'paid_at' => $paidAt ?? now(),
            ]);

            $this->syncOrderPaymentStatus($payment->order);

            return $payment->refresh();
        });
    }

    public function updatePaymentStatus(Payment|int $payment, string $status, array $attributes = []): Payment
    {
        return DB::transaction(function () use ($payment, $status, $attributes) {
            $payment = $this->resolvePayment($payment);

            $payment->update(array_merge(
                Arr::only($attributes, ['provider_reference', 'metadata', 'paid_at']),
                ['status' => $status]
            ));

            $this->syncOrderPaymentStatus($payment->order);

            return $payment->refresh();
        });
    }

    public function createRefund(Payment|int $payment, array $data): Refund
    {
        return DB::transaction(function () use ($payment, $data) {
            $payment = $this->resolvePayment($payment);
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status !== 'paid') {
                throw new RuntimeException('Refunds are only allowed for paid payments.');
            }

            $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
            $paidAmount = (float) $order->payments()->where('status', 'paid')->sum('amount');
            $reservedRefundAmount = (float) $order->refunds()
                ->whereIn('status', ['processed', 'pending'])
                ->sum('amount');
            $availableToRefund = max(0, $paidAmount - $reservedRefundAmount);

            if ((float) $data['amount'] > $availableToRefund) {
                throw new RuntimeException('Refund amount exceeds available paid balance.');
            }

            $refund = Refund::query()->create(array_merge(
                Arr::only($data, [
                    'amount',
                    'reason',
                    'status',
                    'processed_at',
                ]),
                [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'status' => $data['status'] ?? 'pending',
                ]
            ));

            $this->syncOrderPaymentStatus($payment->order);

            return $refund;
        });
    }

    public function updateRefundStatus(Refund|int $refund, string $status, mixed $processedAt = null): Refund
    {
        return DB::transaction(function () use ($refund, $status, $processedAt) {
            $refund = $this->resolveRefund($refund);

            $refund->update([
                'status' => $status,
                'processed_at' => $processedAt ?? ($status === 'processed' ? now() : $refund->processed_at),
            ]);

            $this->syncOrderPaymentStatus($refund->order);

            return $refund->refresh();
        });
    }

    public function syncOrderPaymentStatus(Order|int $order): Order
    {
        $orderId = $order instanceof Order ? $order->id : $order;
        $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
        $paidAmount = (float) $order->payments()->where('status', 'paid')->sum('amount');
        $refundedAmount = (float) $order->refunds()->where('status', 'processed')->sum('amount');
        $netPaid = max(0, $paidAmount - $refundedAmount);
        $grandTotal = (float) $order->grand_total;

        $status = match (true) {
            $grandTotal > 0 && $netPaid >= $grandTotal && $refundedAmount <= 0 => 'paid',
            $paidAmount > 0 && $refundedAmount >= $paidAmount => 'refunded',
            $refundedAmount > 0 => 'partially_refunded',
            $grandTotal > 0 && $netPaid > 0 && $netPaid < $grandTotal => 'partially_paid',
            default => 'unpaid',
        };

        $order->update(['payment_status' => $status]);

        return $order->refresh();
    }

    private function resolveOrder(Order|int $order): Order
    {
        if ($order instanceof Order) {
            return $order;
        }

        return Order::query()->findOrFail($order);
    }

    private function resolvePayment(Payment|int $payment): Payment
    {
        if ($payment instanceof Payment) {
            return $payment;
        }

        return Payment::query()->findOrFail($payment);
    }

    private function resolveRefund(Refund|int $refund): Refund
    {
        if ($refund instanceof Refund) {
            return $refund;
        }

        return Refund::query()->findOrFail($refund);
    }
}
