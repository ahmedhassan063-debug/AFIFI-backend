<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 5000);

        return [
            'order_number' => 'AFIFI-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
            'user_id' => User::factory(),
            'guest_email' => null,
            'guest_phone' => null,
            'currency_id' => Currency::factory(),
            'currency_code' => 'EGP',
            'exchange_rate' => 1.00000000,
            'status' => 'pending_confirmation',
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
            'subtotal' => $subtotal,
            'shipping_fee' => 0,
            'discount_total' => 0,
            'grand_total' => $subtotal,
            'coupon_id' => null,
            'customer_notes' => null,
            'admin_notes' => null,
            'whatsapp_sent_at' => null,
            'confirmed_at' => null,
            'cancelled_at' => null,
        ];
    }
}
