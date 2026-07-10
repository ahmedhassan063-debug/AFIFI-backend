<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function paidPayment(): Payment
    {
        $order = Order::factory()->create([
            'subtotal' => 100,
            'grand_total' => 100,
            'payment_status' => 'paid',
        ]);

        return Payment::factory()->for($order)->create([
            'status' => 'paid',
            'amount' => 100,
        ]);
    }

    public function test_payments_view_only_user_can_list_and_show_payments(): void
    {
        $support = \App\Models\User::factory()->create();
        $support->assignRole('support');
        Sanctum::actingAs($support);

        $payment = $this->paidPayment();

        $this->getJson('/api/admin/payments')->assertOk();
        $this->getJson("/api/admin/payments/{$payment->id}")->assertOk();
    }

    public function test_payments_view_only_user_cannot_mark_payment_paid(): void
    {
        $support = \App\Models\User::factory()->create();
        $support->assignRole('support');
        Sanctum::actingAs($support);

        $payment = Payment::factory()->for(Order::factory())->create(['status' => 'pending']);

        $this->patchJson("/api/admin/payments/{$payment->id}/paid")->assertStatus(403);
    }

    public function test_payments_view_only_user_cannot_update_payment_status(): void
    {
        $support = \App\Models\User::factory()->create();
        $support->assignRole('support');
        Sanctum::actingAs($support);

        $payment = $this->paidPayment();

        $this->patchJson("/api/admin/payments/{$payment->id}/status", [
            'status' => 'failed',
        ])->assertStatus(403);
    }

    public function test_payments_view_only_user_cannot_create_refund(): void
    {
        $support = \App\Models\User::factory()->create();
        $support->assignRole('support');
        Sanctum::actingAs($support);

        $payment = $this->paidPayment();

        $this->postJson("/api/admin/payments/{$payment->id}/refunds", [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'amount' => 10,
            'reason' => 'Test',
        ])->assertStatus(403);
    }

    public function test_super_admin_can_mark_payment_paid(): void
    {
        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('super_admin');
        Sanctum::actingAs($admin);

        $payment = Payment::factory()->for(Order::factory())->create(['status' => 'pending']);

        $this->patchJson("/api/admin/payments/{$payment->id}/paid")->assertOk();
        $this->assertSame('paid', $payment->fresh()->status);
    }
}
