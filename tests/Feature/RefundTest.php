<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RefundTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actingAsRefundAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        Sanctum::actingAs($admin);

        return $admin;
    }

    private function paidOrderWithPayment(float $amount = 100): array
    {
        $order = Order::factory()->create([
            'subtotal' => $amount,
            'grand_total' => $amount,
            'payment_status' => 'paid',
        ]);
        $payment = Payment::factory()->for($order)->create([
            'status' => 'paid',
            'amount' => $amount,
        ]);

        return [$order, $payment];
    }

    private function pendingRefund(Payment $payment, Order $order, float $amount = 40): Refund
    {
        return Refund::query()->create([
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'amount' => $amount,
            'reason' => 'Customer return',
            'status' => 'pending',
        ]);
    }

    public function test_refund_api_rejects_amount_above_available_balance(): void
    {
        $this->actingAsRefundAdmin();

        [$order, $payment] = $this->paidOrderWithPayment();

        $this->postJson("/api/admin/payments/{$payment->id}/refunds", [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'amount' => 150,
            'reason' => 'Too much',
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Refund amount exceeds available paid balance.']);

        $this->assertDatabaseMissing('refunds', ['payment_id' => $payment->id]);
    }

    public function test_refund_status_update_marks_refund_processed(): void
    {
        $this->actingAsRefundAdmin();
        [$order, $payment] = $this->paidOrderWithPayment();
        $refund = $this->pendingRefund($payment, $order);

        $this->patchJson("/api/admin/payments/{$payment->id}/refunds/{$refund->id}/status", [
            'status' => 'processed',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'processed')
            ->assertJsonPath('data.processed_at', fn ($value) => $value !== null);

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => 'processed',
        ]);
        $this->assertSame('partially_refunded', $order->fresh()->payment_status);
    }

    public function test_refund_status_update_marks_refund_failed(): void
    {
        $this->actingAsRefundAdmin();
        [$order, $payment] = $this->paidOrderWithPayment();
        $refund = $this->pendingRefund($payment, $order);

        $this->patchJson("/api/admin/payments/{$payment->id}/refunds/{$refund->id}/status", [
            'status' => 'failed',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'failed');

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => 'failed',
        ]);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_refund_status_update_rejects_payment_refund_mismatch(): void
    {
        $this->actingAsRefundAdmin();
        [$orderA, $paymentA] = $this->paidOrderWithPayment();
        [, $paymentB] = $this->paidOrderWithPayment();
        $refund = $this->pendingRefund($paymentA, $orderA);

        $this->patchJson("/api/admin/payments/{$paymentB->id}/refunds/{$refund->id}/status", [
            'status' => 'processed',
        ])->assertStatus(403);
    }

    public function test_refund_status_update_requires_authentication(): void
    {
        [$order, $payment] = $this->paidOrderWithPayment();
        $refund = $this->pendingRefund($payment, $order);

        $this->patchJson("/api/admin/payments/{$payment->id}/refunds/{$refund->id}/status", [
            'status' => 'processed',
        ])->assertStatus(401);
    }

    public function test_refund_status_update_forbidden_without_permission(): void
    {
        $support = User::factory()->create();
        $support->assignRole('support');
        Sanctum::actingAs($support);

        [$order, $payment] = $this->paidOrderWithPayment();
        $refund = $this->pendingRefund($payment, $order);

        $this->patchJson("/api/admin/payments/{$payment->id}/refunds/{$refund->id}/status", [
            'status' => 'processed',
        ])->assertStatus(403);
    }

    public function test_refund_status_update_validation_rejects_invalid_status(): void
    {
        $this->actingAsRefundAdmin();
        [$order, $payment] = $this->paidOrderWithPayment();
        $refund = $this->pendingRefund($payment, $order);

        $this->patchJson("/api/admin/payments/{$payment->id}/refunds/{$refund->id}/status", [
            'status' => 'completed',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertSame('pending', $refund->fresh()->status);
    }

    public function test_refund_status_update_syncs_order_payment_status_to_refunded(): void
    {
        $this->actingAsRefundAdmin();
        [$order, $payment] = $this->paidOrderWithPayment();
        $refund = $this->pendingRefund($payment, $order, 100);

        $this->patchJson("/api/admin/payments/{$payment->id}/refunds/{$refund->id}/status", [
            'status' => 'processed',
        ])->assertOk();

        $this->assertSame('refunded', $order->fresh()->payment_status);
    }
}
