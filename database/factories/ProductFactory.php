<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'brand_id' => null,
            'category_id' => Category::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 999999),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'gender' => fake()->randomElement(['men', 'women', 'unisex']),
            'badge' => null,
            'base_price' => fake()->randomFloat(2, 100, 5000),
            'compare_at_price' => null,
            'has_variants' => true,
            'is_active' => true,
            'is_new_arrival' => false,
            'is_best_seller' => false,
            'is_featured_drop' => false,
            'meta_title' => null,
            'meta_description' => null,
            'published_at' => now(),
            'sort_order' => 0,
        ];
    }
}
