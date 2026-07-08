<?php

namespace Database\Factories;

use App\Models\ProductVariant;
use App\Models\StockReservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockReservation>
 */
class StockReservationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariant::factory(),
            'user_id' => User::factory(),
            'session_id' => null,
            'quantity' => 1,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(15),
            'notes' => null,
        ];
    }
}
