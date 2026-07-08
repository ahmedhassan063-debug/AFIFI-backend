<?php

namespace Database\Seeders;

use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

class ShippingZoneSeeder extends Seeder
{
    /**
     * Seed shipping zones.
     */
    public function run(): void
    {
        $zones = [
            ['name' => 'Cairo & Giza', 'code' => 'cairo_giza'],
            ['name' => 'Alexandria & Coast', 'code' => 'alexandria_coast'],
            ['name' => 'Delta', 'code' => 'delta'],
            ['name' => 'Upper Egypt', 'code' => 'upper_egypt'],
        ];

        foreach ($zones as $zone) {
            ShippingZone::updateOrCreate(
                ['code' => $zone['code']],
                [
                    'name' => $zone['name'],
                    'is_active' => true,
                ],
            );
        }
    }
}
