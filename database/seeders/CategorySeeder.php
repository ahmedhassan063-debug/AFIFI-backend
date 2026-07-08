<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed basic categories.
     */
    public function run(): void
    {
        $parents = [
            ['name' => 'Men', 'slug' => 'men', 'gender' => 'men'],
            ['name' => 'Women', 'slug' => 'women', 'gender' => 'women'],
            ['name' => 'Unisex', 'slug' => 'unisex', 'gender' => 'unisex'],
        ];

        foreach ($parents as $index => $parent) {
            Category::updateOrCreate(
                ['slug' => $parent['slug']],
                [
                    'parent_id' => null,
                    'image_media_id' => null,
                    'name' => $parent['name'],
                    'gender' => $parent['gender'],
                    'description' => null,
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );
        }

        $children = [
            ['parent' => 'men', 'name' => 'T-Shirts', 'slug' => 'men-t-shirts', 'gender' => 'men'],
            ['parent' => 'men', 'name' => 'Shirts', 'slug' => 'men-shirts', 'gender' => 'men'],
            ['parent' => 'men', 'name' => 'Pants', 'slug' => 'men-pants', 'gender' => 'men'],
            ['parent' => 'men', 'name' => 'Jackets', 'slug' => 'men-jackets', 'gender' => 'men'],
            ['parent' => 'women', 'name' => 'T-Shirts', 'slug' => 'women-t-shirts', 'gender' => 'women'],
            ['parent' => 'women', 'name' => 'Shirts', 'slug' => 'women-shirts', 'gender' => 'women'],
            ['parent' => 'women', 'name' => 'Pants', 'slug' => 'women-pants', 'gender' => 'women'],
            ['parent' => 'women', 'name' => 'Jackets', 'slug' => 'women-jackets', 'gender' => 'women'],
            ['parent' => 'women', 'name' => 'Dresses', 'slug' => 'women-dresses', 'gender' => 'women'],
            ['parent' => 'unisex', 'name' => 'Accessories', 'slug' => 'unisex-accessories', 'gender' => 'unisex'],
        ];

        foreach ($children as $index => $child) {
            $parent = Category::where('slug', $child['parent'])->first();

            if (! $parent) {
                continue;
            }

            Category::updateOrCreate(
                ['slug' => $child['slug']],
                [
                    'parent_id' => $parent->id,
                    'image_media_id' => null,
                    'name' => $child['name'],
                    'gender' => $child['gender'],
                    'description' => null,
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );
        }
    }
}
