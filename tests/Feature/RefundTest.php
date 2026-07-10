<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
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

    public function test_refund_api_rejects_amount_above_available_balance(): void
    {
        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('super_admin');
        Sanctum::actingAs($admin);

        $order = Order::factory()->create([
            'subtotal' => 100,
            'grand_total' => 100,
            'payment_status' => 'paid',
        ]);
        $payment = Payment::factory()->for($order)->create([
            'status' => 'paid',
            'amount' => 100,
        ]);

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
}
