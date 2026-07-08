<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = 1;
        $unitPrice = fake()->randomFloat(2, 100, 5000);

        return [
            'order_id' => Order::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'product_name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-#####')),
            'barcode' => fake()->unique()->ean13(),
            'color_name' => fake()->safeColorName(),
            'size_name' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => round($unitPrice * $quantity, 2),
        ];
    }
}
