<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Currency::factory()->create([
            'code' => 'EGP',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    private function validAddress(): array
    {
        return [
            'full_name' => 'Jane Doe',
            'phone' => '01000000000',
            'governorate_name' => 'Cairo',
            'shipping_zone_code' => 'CAI',
            'city' => 'Cairo',
            'street' => '123 Main St',
        ];
    }

    private function addCartItem(User $user, array $variantAttributes = [], int $quantity = 1, ?float $unitPrice = 100): CartItem
    {
        $variant = ProductVariant::factory()->create(array_merge([
            'stock' => 10,
            'is_active' => true,
            'price_override' => null,
        ], $variantAttributes));

        if ($unitPrice !== null) {
            $variant->product->update(['base_price' => $unitPrice]);
            $variant->refresh()->load('product');
        }

        $resolvedPrice = $variant->price_override ?? (float) $variant->product->base_price;

        $cart = Cart::factory()->for($user)->create();

        return CartItem::factory()
            ->for($cart)
            ->for($variant, 'productVariant')
            ->create([
                'quantity' => $quantity,
                'unit_price_snapshot' => $resolvedPrice,
            ]);
    }

    public function test_successful_checkout_returns_created_order(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->addCartItem($user);

        $response = $this->postJson('/api/checkout', [
            'payment_method' => 'cod',
            'address' => $this->validAddress(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'pending_confirmation');
        $response->assertJsonPath('data.payment_status', 'unpaid');
    }

    public function test_checkout_fails_with_empty_cart(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/checkout', [
            'payment_method' => 'cod',
            'address' => $this->validAddress(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Cart is empty.']);
    }

    public function test_checkout_fails_with_insufficient_stock(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->addCartItem($user, ['stock' => 1], quantity: 5);

        $response = $this->postJson('/api/checkout', [
            'payment_method' => 'cod',
            'address' => $this->validAddress(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Cart contains a product variant with insufficient stock.']);
    }

    public function test_checkout_fails_with_invalid_coupon(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->addCartItem($user);

        $coupon = Coupon::factory()->create([
            'code' => 'EXPIRED10',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->postJson('/api/checkout', [
            'payment_method' => 'cod',
            'coupon_code' => $coupon->code,
            'address' => $this->validAddress(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Coupon has expired.']);
    }

    public function test_checkout_fails_with_inactive_product_variant(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->addCartItem($user, ['is_active' => false]);

        $response = $this->postJson('/api/checkout', [
            'payment_method' => 'cod',
            'address' => $this->validAddress(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Cart contains an unavailable product variant.']);
    }

    public function test_successful_checkout_creates_order_in_database(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->addCartItem($user, [], quantity: 2);

        $response = $this->postJson('/api/checkout', [
            'payment_method' => 'cod',
            'address' => $this->validAddress(),
        ]);

        $response->assertCreated();
        $orderId = $response->json('data.id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'user_id' => $user->id,
            'subtotal' => 200,
            'grand_total' => 200,
        ]);
        $this->assertDatabaseHas('order_addresses', [
            'order_id' => $orderId,
            'type' => 'shipping',
        ]);
    }

    public function test_successful_checkout_creates_payment_record(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->addCartItem($user);

        $response = $this->postJson('/api/checkout', [
            'payment_method' => 'cod',
            'address' => $this->validAddress(),
        ]);

        $response->assertCreated();
        $orderId = $response->json('data.id');

        $this->assertDatabaseHas('payments', [
            'order_id' => $orderId,
            'provider' => 'cod',
            'amount' => 100,
            'status' => 'pending',
        ]);
    }

    public function test_successful_checkout_clears_cart(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $cartItem = $this->addCartItem($user);

        $response = $this->postJson('/api/checkout', [
            'payment_method' => 'cod',
            'address' => $this->validAddress(),
        ]);

        $response->assertCreated();
        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
        $this->assertSame(0, Cart::query()->find($cartItem->cart_id)->items()->count());
    }

    public function test_checkout_ignores_client_supplied_shipping_fee(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->addCartItem($user, [], quantity: 1);

        $response = $this->postJson('/api/checkout', [
            'payment_method' => 'cod',
            'shipping_fee' => 0,
            'address' => $this->validAddress(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.shipping_fee', '0.00');
        $response->assertJsonPath('data.grand_total', '100.00');
    }

    public function test_checkout_uses_current_server_price_not_stale_cart_snapshot(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $variant = ProductVariant::factory()->create([
            'stock' => 10,
            'is_active' => true,
            'price_override' => null,
        ]);
        $variant->product->update(['base_price' => 150]);

        $cart = Cart::factory()->for($user)->create();
        CartItem::factory()
            ->for($cart)
            ->for($variant, 'productVariant')
            ->create([
                'quantity' => 1,
                'unit_price_snapshot' => 50,
            ]);

        $response = $this->postJson('/api/checkout', [
            'payment_method' => 'cod',
            'address' => $this->validAddress(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.subtotal', '150.00');
        $response->assertJsonPath('data.grand_total', '150.00');

        $orderId = $response->json('data.id');
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'unit_price' => 150,
            'line_total' => 150,
        ]);
    }

    public function test_checkout_fails_with_inactive_product(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $variant = ProductVariant::factory()->create(['stock' => 10, 'is_active' => true]);
        $variant->product->update(['is_active' => false]);

        $cart = Cart::factory()->for($user)->create();
        CartItem::factory()
            ->for($cart)
            ->for($variant, 'productVariant')
            ->create([
                'quantity' => 1,
                'unit_price_snapshot' => 100,
            ]);

        $response = $this->postJson('/api/checkout', [
            'payment_method' => 'cod',
            'address' => $this->validAddress(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Cart contains an unavailable product.']);
    }

    public function test_checkout_totals_include_coupon_discount_and_shipping(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->addCartItem($user, [], quantity: 2);

        $coupon = Coupon::factory()->create([
            'code' => 'SAVE20',
            'type' => 'fixed',
            'value' => 20,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->postJson('/api/checkout', [
            'payment_method' => 'cod',
            'coupon_code' => $coupon->code,
            'address' => $this->validAddress(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.subtotal', '200.00');
        $response->assertJsonPath('data.discount_total', '20.00');
        $response->assertJsonPath('data.shipping_fee', '0.00');
        $response->assertJsonPath('data.grand_total', '180.00');
    }

    public function test_second_checkout_fails_when_stock_is_exhausted_by_first(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 1, 'is_active' => true]);

        $firstUser = User::factory()->create();
        Sanctum::actingAs($firstUser);
        $cart = Cart::factory()->for($firstUser)->create();
        CartItem::factory()
            ->for($cart)
            ->for($variant, 'productVariant')
            ->create(['quantity' => 1, 'unit_price_snapshot' => (float) $variant->product->base_price]);

        $this->postJson('/api/checkout', [
            'payment_method' => 'cod',
            'address' => $this->validAddress(),
        ])->assertCreated();

        $secondUser = User::factory()->create();
        Sanctum::actingAs($secondUser);
        $secondCart = Cart::factory()->for($secondUser)->create();
        CartItem::factory()
            ->for($secondCart)
            ->for($variant, 'productVariant')
            ->create(['quantity' => 1, 'unit_price_snapshot' => (float) $variant->product->base_price]);

        $this->postJson('/api/checkout', [
            'payment_method' => 'cod',
            'address' => $this->validAddress(),
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Cart contains a product variant with insufficient stock.']);
    }
}
