<?php

namespace Database\Seeders;

use App\Models\Size;
use Illuminate\Database\Seeder;

class SizeSeeder extends Seeder
{
    /**
     * Seed apparel sizes.
     */
    public function run(): void
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

        foreach ($sizes as $index => $size) {
            Size::updateOrCreate(
                ['slug' => strtolower($size)],
                [
                    'name' => $size,
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );
        }
    }
}
