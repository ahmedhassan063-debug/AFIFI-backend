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

    private function addCartItem(User $user, array $variantAttributes = [], int $quantity = 1): CartItem
    {
        $variant = ProductVariant::factory()->create(array_merge([
            'stock' => 10,
            'is_active' => true,
        ], $variantAttributes));

        $cart = Cart::factory()->for($user)->create();

        return CartItem::factory()
            ->for($cart)
            ->for($variant, 'productVariant')
            ->create([
                'quantity' => $quantity,
                'unit_price_snapshot' => 100,
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
}
