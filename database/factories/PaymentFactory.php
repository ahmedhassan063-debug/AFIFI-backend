<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => 'cod',
            'provider_reference' => null,
            'amount' => fake()->randomFloat(2, 100, 5000),
            'currency' => 'EGP',
            'status' => 'pending',
            'metadata' => null,
            'paid_at' => null,
        ];
    }
}
