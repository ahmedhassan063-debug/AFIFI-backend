<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Currency;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Support\EnablesManualPayments;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
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

    public function test_cancelling_order_restock_inventory(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $variant = ProductVariant::factory()->create(['stock' => 10, 'is_active' => true]);
        $cart = Cart::factory()->for($user)->create();
        CartItem::factory()
            ->for($cart)
            ->for($variant, 'productVariant')
            ->create([
                'quantity' => 2,
                'unit_price_snapshot' => (float) $variant->product->base_price,
            ]);

        $checkout = $this->postJson('/api/checkout', [
            'payment_method' => 'instapay',
            'address' => [
                'full_name' => 'Jane Doe',
                'phone' => '01000000000',
                'governorate_name' => 'Cairo',
                'shipping_zone_code' => 'CAI',
                'city' => 'Cairo',
                'street' => '123 Main St',
            ],
        ]);

        $checkout->assertCreated();
        $this->assertSame(8, $variant->fresh()->stock);

        $orderId = $checkout->json('data.id');

        $this->postJson("/api/orders/{$orderId}/cancel")->assertOk();

        $this->assertSame(10, $variant->fresh()->stock);
        $this->assertSame('cancelled', Order::query()->find($orderId)->status);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'restock',
            'quantity_delta' => 2,
        ]);
    }
}
