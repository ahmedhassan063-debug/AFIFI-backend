<?php

namespace Database\Factories;

use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        $colorId = Color::query()->create([
            'name' => fake()->unique()->safeColorName().'-'.fake()->unique()->numberBetween(1000, 999999),
            'slug' => Str::slug(fake()->unique()->word()).'-'.fake()->unique()->numberBetween(1000, 999999),
            'hex_code' => fake()->hexColor(),
            'is_active' => true,
            'sort_order' => 0,
        ])->id;

        $sizeId = Size::query()->create([
            'name' => fake()->unique()->lexify('SZ??'),
            'slug' => Str::slug(fake()->unique()->lexify('sz??')),
            'is_active' => true,
            'sort_order' => 0,
        ])->id;

        return [
            'product_id' => Product::factory(),
            'color_id' => $colorId,
            'size_id' => $sizeId,
            'sku' => Str::upper(Str::random(10)),
            'barcode' => fake()->unique()->ean13(),
            'price_override' => null,
            'stock' => 100,
            'is_active' => true,
        ];
    }
}
