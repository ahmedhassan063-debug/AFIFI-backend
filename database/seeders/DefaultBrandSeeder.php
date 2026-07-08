<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class DefaultBrandSeeder extends Seeder
{
    /**
     * Seed the default brand.
     */
    public function run(): void
    {
        Brand::updateOrCreate(
            ['slug' => 'afifi'],
            [
                'logo_media_id' => null,
                'name' => 'AFIFI',
                'description' => null,
                'website_url' => null,
                'is_active' => true,
                'sort_order' => 0,
            ],
        );
    }
}
