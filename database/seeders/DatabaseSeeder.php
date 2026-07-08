<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            CurrencySeeder::class,
            ShippingZoneSeeder::class,
            ShippingRateSeeder::class,
            GovernorateSeeder::class,
            ColorSeeder::class,
            SizeSeeder::class,
            DefaultBrandSeeder::class,
            CategorySeeder::class,
            CoreSettingSeeder::class,
            CmsDefaultSeeder::class,
            DemoProductSeeder::class,
            ProductMediaSeeder::class,
        ]);
    }
}
