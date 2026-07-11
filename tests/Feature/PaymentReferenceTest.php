<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\Support\EnablesManualPayments;
use Tests\TestCase;

class PaymentReferenceTest extends TestCase
{
    use EnablesManualPayments;

    protected function setUp(): void
    {
        parent::setUp();

        Currency::factory()->create([
            'code' => 'EGP',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->seedEnabledManualPayments();
    }

    private function checkoutOrder(User $user, string $paymentMethod = 'instapay'): Order
    {
        Sanctum::actingAs($user);

        $variant = ProductVariant::factory()->create(['stock' => 10, 'is_active' => true]);
        $cart = Cart::factory()->for($user)->create();
        CartItem::factory()
            ->for($cart)
            ->for($variant, 'productVariant')
            ->create([
                'quantity' => 1,
                'unit_price_snapshot' => (float) $variant->product->base_price,
            ]);

        $response = $this->postJson('/api/checkout', [
            'payment_method' => $paymentMethod,
            'address' => [
                'full_name' => 'Jane Doe',
                'phone' => '01000000000',
                'governorate_name' => 'Cairo',
                'shipping_zone_code' => 'CAI',
                'city' => 'Cairo',
                'street' => '123 Main St',
            ],
        ]);

        $response->assertCreated();

        return Order::query()->findOrFail($response->json('data.id'));
    }

    public function test_owner_can_submit_payment_reference(): void
    {
        $user = User::factory()->create();
        $order = $this->checkoutOrder($user);

        $response = $this->putJson("/api/orders/{$order->id}/payment-reference", [
            'provider_reference' => 'TXN-12345',
        ]);

        $response->assertOk();
        $response->assertJsonPath('payment.provider_reference', 'TXN-12345');
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider_reference' => 'TXN-12345',
            'status' => 'pending',
        ]);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
        $this->assertSame('pending_confirmation', $order->fresh()->status);
    }

    public function test_owner_can_update_pending_payment_reference(): void
    {
        $user = User::factory()->create();
        $order = $this->checkoutOrder($user);

        $this->putJson("/api/orders/{$order->id}/payment-reference", [
            'provider_reference' => 'TXN-OLD',
        ])->assertOk();

        $response = $this->putJson("/api/orders/{$order->id}/payment-reference", [
            'provider_reference' => 'TXN-NEW',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider_reference' => 'TXN-NEW',
            'status' => 'pending',
        ]);
    }

    public function test_other_user_cannot_submit_payment_reference(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = $this->checkoutOrder($owner);

        Sanctum::actingAs($other);

        $this->putJson("/api/orders/{$order->id}/payment-reference", [
            'provider_reference' => 'TXN-12345',
        ])->assertNotFound();
    }

    public function test_guest_cannot_submit_payment_reference(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'status' => 'pending_confirmation',
            'payment_status' => 'unpaid',
            'payment_method' => 'instapay',
        ]);
        Payment::factory()->for($order)->create([
            'provider' => 'instapay',
            'status' => 'pending',
        ]);

        $this->putJson("/api/orders/{$order->id}/payment-reference", [
            'provider_reference' => 'TXN-12345',
        ])->assertUnauthorized();
    }

    public function test_paid_payment_cannot_be_modified(): void
    {
        $user = User::factory()->create();
        $order = $this->checkoutOrder($user);
        $payment = $order->payments()->first();
        $payment->update(['status' => 'paid', 'paid_at' => now()]);
        $order->update(['payment_status' => 'paid']);

        $this->putJson("/api/orders/{$order->id}/payment-reference", [
            'provider_reference' => 'TXN-12345',
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Cannot submit payment reference for a paid or finalized order.']);
    }

    public function test_failed_payment_cannot_be_modified(): void
    {
        $user = User::factory()->create();
        $order = $this->checkoutOrder($user);
        $order->payments()->first()->update(['status' => 'failed']);

        $this->putJson("/api/orders/{$order->id}/payment-reference", [
            'provider_reference' => 'TXN-12345',
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'No pending manual payment found for this order.']);
    }

    public function test_reference_validation_errors_return_422(): void
    {
        $user = User::factory()->create();
        $order = $this->checkoutOrder($user);

        $this->putJson("/api/orders/{$order->id}/payment-reference", [
            'provider_reference' => 'ab',
        ])->assertStatus(422)->assertJsonValidationErrors(['provider_reference']);

        $this->putJson("/api/orders/{$order->id}/payment-reference", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['provider_reference']);
    }

    public function test_submission_trims_provider_reference(): void
    {
        $user = User::factory()->create();
        $order = $this->checkoutOrder($user);

        $this->putJson("/api/orders/{$order->id}/payment-reference", [
            'provider_reference' => '  TXN-TRIMMED  ',
        ])->assertOk();

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider_reference' => 'TXN-TRIMMED',
        ]);
    }

    public function test_admin_mark_as_paid_still_works_after_reference_submission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $order = $this->checkoutOrder($user);

        $this->putJson("/api/orders/{$order->id}/payment-reference", [
            'provider_reference' => 'TXN-12345',
        ])->assertOk();

        $payment = $order->payments()->first();
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/payments/{$payment->id}/paid")->assertOk();

        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertSame('TXN-12345', $payment->provider_reference);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_admin_payment_show_exposes_provider_reference(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $order = $this->checkoutOrder($user);
        $payment = $order->payments()->first();
        $payment->update(['provider_reference' => 'TXN-ADMIN-VIEW']);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        Sanctum::actingAs($admin);

        $this->getJson("/api/admin/payments/{$payment->id}")
            ->assertOk()
            ->assertJsonPath('data.provider', 'instapay')
            ->assertJsonPath('data.provider_reference', 'TXN-ADMIN-VIEW')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.order_id', $order->id);
    }
}
