<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->currencyCode(),
            'name' => fake()->currencyCode(),
            'symbol' => 'E£',
            'decimal_places' => 2,
            'exchange_rate' => 1.00000000,
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
