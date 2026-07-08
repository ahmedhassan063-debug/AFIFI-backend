<?php

namespace Database\Factories;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShippingRate>
 */
class ShippingRateFactory extends Factory
{
    public function definition(): array
    {
        $shippingZoneId = ShippingZone::query()->create([
            'name' => fake()->city(),
            'code' => 'ZN-'.Str::upper(Str::random(6)),
            'is_active' => true,
        ])->id;

        return [
            'shipping_zone_id' => $shippingZoneId,
            'fee' => fake()->randomFloat(2, 20, 150),
            'estimated_days_min' => 1,
            'estimated_days_max' => 5,
            'is_active' => true,
            'valid_from' => null,
            'valid_until' => null,
        ];
    }
}
