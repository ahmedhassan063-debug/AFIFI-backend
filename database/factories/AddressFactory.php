<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Governorate;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    public function definition(): array
    {
        $shippingZoneId = ShippingZone::query()->create([
            'name' => fake()->city(),
            'code' => 'ZN-'.Str::upper(Str::random(6)),
            'is_active' => true,
        ])->id;

        $governorateId = Governorate::query()->create([
            'shipping_zone_id' => $shippingZoneId,
            'name' => fake()->unique()->city(),
            'name_ar' => null,
            'is_active' => true,
        ])->id;

        return [
            'user_id' => User::factory(),
            'label' => 'Home',
            'full_name' => fake()->name(),
            'phone' => fake()->numerify('01#########'),
            'governorate_id' => $governorateId,
            'city' => fake()->city(),
            'area' => fake()->streetName(),
            'street' => fake()->streetAddress(),
            'building' => (string) fake()->numberBetween(1, 200),
            'floor' => (string) fake()->numberBetween(1, 20),
            'postal_code' => fake()->postcode(),
            'is_default' => false,
        ];
    }
}
