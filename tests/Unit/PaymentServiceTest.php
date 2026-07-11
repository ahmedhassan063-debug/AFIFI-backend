<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\PaymentService;
use RuntimeException;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    private PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PaymentService::class);
    }

    private function makeOrder(float $grandTotal): Order
    {
        return Order::factory()->create([
            'subtotal' => $grandTotal,
            'grand_total' => $grandTotal,
        ]);
    }

    public function test_sync_order_payment_status_is_unpaid_with_no_payments(): void
    {
        $order = $this->makeOrder(100);

        $result = $this->service->syncOrderPaymentStatus($order);

        $this->assertSame('unpaid', $result->payment_status);
    }

    public function test_sync_order_payment_status_is_partially_paid(): void
    {
        $order = $this->makeOrder(100);
        Payment::factory()->for($order)->create(['status' => 'paid', 'amount' => 40]);

        $result = $this->service->syncOrderPaymentStatus($order);

        $this->assertSame('partially_paid', $result->payment_status);
    }

    public function test_sync_order_payment_status_is_paid(): void
    {
        $order = $this->makeOrder(100);
        Payment::factory()->for($order)->create(['status' => 'paid', 'amount' => 100]);

        $result = $this->service->syncOrderPaymentStatus($order);

        $this->assertSame('paid', $result->payment_status);
    }

    public function test_sync_order_payment_status_is_partially_refunded(): void
    {
        $order = $this->makeOrder(100);
        $payment = Payment::factory()->for($order)->create(['status' => 'paid', 'amount' => 100]);

        Refund::query()->create([
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'amount' => 30,
            'status' => 'processed',
        ]);

        $result = $this->service->syncOrderPaymentStatus($order);

        $this->assertSame('partially_refunded', $result->payment_status);
    }

    public function test_sync_order_payment_status_is_refunded(): void
    {
        $order = $this->makeOrder(100);
        $payment = Payment::factory()->for($order)->create(['status' => 'paid', 'amount' => 100]);

        Refund::query()->create([
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'amount' => 100,
            'status' => 'processed',
        ]);

        $result = $this->service->syncOrderPaymentStatus($order);

        $this->assertSame('refunded', $result->payment_status);
    }

    public function test_create_refund_creates_refund_successfully(): void
    {
        $order = $this->makeOrder(100);
        $payment = Payment::factory()->for($order)->create(['status' => 'paid', 'amount' => 100]);

        $refund = $this->service->createRefund($payment, [
            'amount' => 40,
            'reason' => 'Damaged item',
            'status' => 'processed',
        ]);

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'amount' => 40,
            'status' => 'processed',
        ]);
        $this->assertSame('partially_refunded', $order->fresh()->payment_status);
    }

    public function test_create_refund_blocks_over_refund(): void
    {
        $order = $this->makeOrder(100);
        $payment = Payment::factory()->for($order)->create(['status' => 'paid', 'amount' => 100]);
        $this->service->syncOrderPaymentStatus($order);

        try {
            $this->service->createRefund($payment, ['amount' => 150]);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Refund amount exceeds available paid balance.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('refunds', ['payment_id' => $payment->id]);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_create_refund_blocks_pending_refunds_from_over_committing_balance(): void
    {
        $order = $this->makeOrder(100);
        $payment = Payment::factory()->for($order)->create(['status' => 'paid', 'amount' => 100]);

        $this->service->createRefund($payment, [
            'amount' => 60,
            'reason' => 'First refund',
            'status' => 'pending',
        ]);

        try {
            $this->service->createRefund($payment, ['amount' => 50]);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Refund amount exceeds available paid balance.', $exception->getMessage());
        }

        $this->assertSame(1, Refund::query()->where('payment_id', $payment->id)->count());
    }

    public function test_create_refund_requires_paid_payment(): void
    {
        $order = $this->makeOrder(100);
        $payment = Payment::factory()->for($order)->create(['status' => 'pending', 'amount' => 100]);

        try {
            $this->service->createRefund($payment, ['amount' => 10]);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Refunds are only allowed for paid payments.', $exception->getMessage());
        }
    }
}
