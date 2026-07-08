<?php

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    /**
     * Seed core colors.
     */
    public function run(): void
    {
        $colors = [
            ['name' => 'Black', 'slug' => 'black', 'hex_code' => '#000000'],
            ['name' => 'White', 'slug' => 'white', 'hex_code' => '#FFFFFF'],
            ['name' => 'Gray', 'slug' => 'gray', 'hex_code' => '#808080'],
            ['name' => 'Navy', 'slug' => 'navy', 'hex_code' => '#000080'],
            ['name' => 'Beige', 'slug' => 'beige', 'hex_code' => '#F5F5DC'],
            ['name' => 'Brown', 'slug' => 'brown', 'hex_code' => '#8B4513'],
            ['name' => 'Blue', 'slug' => 'blue', 'hex_code' => '#0000FF'],
            ['name' => 'Green', 'slug' => 'green', 'hex_code' => '#008000'],
            ['name' => 'Red', 'slug' => 'red', 'hex_code' => '#FF0000'],
        ];

        foreach ($colors as $index => $color) {
            Color::updateOrCreate(
                ['slug' => $color['slug']],
                [
                    'name' => $color['name'],
                    'hex_code' => $color['hex_code'],
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );
        }
    }
}
