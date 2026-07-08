<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

class ShippingRateSeeder extends Seeder
{
    /**
     * Seed basic shipping rates.
     */
    public function run(): void
    {
        $rates = [
            'cairo_giza' => ['fee' => 70, 'estimated_days_min' => 1, 'estimated_days_max' => 3],
            'alexandria_coast' => ['fee' => 85, 'estimated_days_min' => 2, 'estimated_days_max' => 4],
            'delta' => ['fee' => 90, 'estimated_days_min' => 2, 'estimated_days_max' => 5],
            'upper_egypt' => ['fee' => 120, 'estimated_days_min' => 3, 'estimated_days_max' => 7],
        ];

        foreach ($rates as $zoneCode => $rate) {
            $zone = ShippingZone::where('code', $zoneCode)->first();

            if (! $zone) {
                continue;
            }

            ShippingRate::updateOrCreate(
                ['shipping_zone_id' => $zone->id],
                [
                    'fee' => $rate['fee'],
                    'estimated_days_min' => $rate['estimated_days_min'],
                    'estimated_days_max' => $rate['estimated_days_max'],
                    'is_active' => true,
                    'valid_from' => null,
                    'valid_until' => null,
                ],
            );
        }
    }
}
