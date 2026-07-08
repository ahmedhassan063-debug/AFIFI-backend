<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Campaign;
use App\Models\CampaignProduct;
use App\Models\Category;
use App\Models\Color;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoProductSeeder extends Seeder
{
    /**
     * Seed realistic demo products, variants, a coupon, and a campaign
     * so the full catalog-to-checkout flow can be exercised end to end.
     */
    public function run(): void
    {
        $brand = Brand::where('slug', 'afifi')->first();
        $colors = Color::query()->pluck('id', 'slug');
        $sizes = Size::query()->pluck('id', 'slug');

        if ($colors->isEmpty() || $sizes->isEmpty()) {
            return;
        }

        $products = $this->demoProducts();
        $createdProductIds = [];

        foreach ($products as $definition) {
            $category = Category::where('slug', $definition['category_slug'])->first();

            if (! $category) {
                continue;
            }

            $product = Product::updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'brand_id' => $brand?->id,
                    'category_id' => $category->id,
                    'name' => $definition['name'],
                    'short_description' => $definition['short_description'],
                    'description' => $definition['description'],
                    'gender' => $definition['gender'],
                    'badge' => $definition['badge'] ?? null,
                    'base_price' => $definition['base_price'],
                    'compare_at_price' => $definition['compare_at_price'] ?? null,
                    'has_variants' => true,
                    'is_active' => true,
                    'is_new_arrival' => $definition['is_new_arrival'] ?? false,
                    'is_best_seller' => $definition['is_best_seller'] ?? false,
                    'is_featured_drop' => $definition['is_featured_drop'] ?? false,
                    'published_at' => now(),
                    'sort_order' => $definition['sort_order'],
                ],
            );

            $createdProductIds[$definition['slug']] = $product->id;

            foreach ($definition['variants'] as $variant) {
                $colorId = $colors[$variant['color']] ?? null;
                $sizeId = $sizes[$variant['size']] ?? null;

                if (! $colorId || ! $sizeId) {
                    continue;
                }

                $sku = Str::upper($definition['sku_prefix'].'-'.$variant['color'].'-'.$variant['size']);

                ProductVariant::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'color_id' => $colorId,
                        'size_id' => $sizeId,
                    ],
                    [
                        'sku' => $sku,
                        'barcode' => null,
                        'price_override' => null,
                        'stock' => $variant['stock'],
                        'is_active' => true,
                    ],
                );
            }
        }

        $this->seedCoupon();
        $this->seedCampaign($createdProductIds);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function demoProducts(): array
    {
        return [
            [
                'slug' => 'classic-crew-t-shirt',
                'sku_prefix' => 'CCT',
                'name' => 'Classic Crew T-Shirt',
                'short_description' => 'Everyday cotton crew-neck tee.',
                'description' => 'Soft, breathable cotton t-shirt built for daily wear with a classic crew neckline.',
                'category_slug' => 'men-t-shirts',
                'gender' => 'men',
                'base_price' => 350.00,
                'compare_at_price' => null,
                'is_best_seller' => true,
                'sort_order' => 1,
                'variants' => [
                    ['color' => 'black', 'size' => 'm', 'stock' => 40],
                    ['color' => 'black', 'size' => 'l', 'stock' => 35],
                    ['color' => 'white', 'size' => 'm', 'stock' => 30],
                ],
            ],
            [
                'slug' => 'essential-v-neck-tee',
                'sku_prefix' => 'EVN',
                'name' => 'Essential V-Neck Tee',
                'short_description' => 'Lightweight v-neck essential.',
                'description' => 'A wardrobe essential v-neck tee made from a soft cotton blend.',
                'category_slug' => 'men-t-shirts',
                'gender' => 'men',
                'base_price' => 320.00,
                'sort_order' => 2,
                'variants' => [
                    ['color' => 'gray', 'size' => 's', 'stock' => 25],
                    ['color' => 'navy', 'size' => 'm', 'stock' => 28],
                ],
            ],
            [
                'slug' => 'oxford-button-down-shirt',
                'sku_prefix' => 'OBD',
                'name' => 'Oxford Button-Down Shirt',
                'short_description' => 'Crisp oxford weave shirt.',
                'description' => 'A tailored oxford shirt with a button-down collar, ideal for smart-casual looks.',
                'category_slug' => 'men-shirts',
                'gender' => 'men',
                'base_price' => 650.00,
                'compare_at_price' => 750.00,
                'sort_order' => 3,
                'variants' => [
                    ['color' => 'white', 'size' => 'm', 'stock' => 20],
                    ['color' => 'blue', 'size' => 'l', 'stock' => 18],
                    ['color' => 'blue', 'size' => 'xl', 'stock' => 15],
                ],
            ],
            [
                'slug' => 'linen-casual-shirt',
                'sku_prefix' => 'LCS',
                'name' => 'Linen Casual Shirt',
                'short_description' => 'Breathable linen-blend shirt.',
                'description' => 'A relaxed-fit linen-blend shirt designed for warm-weather comfort.',
                'category_slug' => 'men-shirts',
                'gender' => 'men',
                'base_price' => 700.00,
                'sort_order' => 4,
                'variants' => [
                    ['color' => 'beige', 'size' => 'm', 'stock' => 22],
                    ['color' => 'beige', 'size' => 'l', 'stock' => 18],
                ],
            ],
            [
                'slug' => 'slim-fit-chino-pants',
                'sku_prefix' => 'SFC',
                'name' => 'Slim Fit Chino Pants',
                'short_description' => 'Tailored slim-fit chinos.',
                'description' => 'Versatile slim-fit chino pants crafted from stretch cotton twill.',
                'category_slug' => 'men-pants',
                'gender' => 'men',
                'base_price' => 750.00,
                'sort_order' => 5,
                'variants' => [
                    ['color' => 'navy', 'size' => 'm', 'stock' => 30],
                    ['color' => 'beige', 'size' => 'l', 'stock' => 26],
                    ['color' => 'black', 'size' => 'xl', 'stock' => 20],
                ],
            ],
            [
                'slug' => 'straight-leg-denim',
                'sku_prefix' => 'SLD',
                'name' => 'Straight Leg Denim',
                'short_description' => 'Classic straight-leg denim jeans.',
                'description' => 'Durable straight-leg denim jeans with a timeless five-pocket design.',
                'category_slug' => 'men-pants',
                'gender' => 'men',
                'base_price' => 820.00,
                'sort_order' => 6,
                'variants' => [
                    ['color' => 'blue', 'size' => 'm', 'stock' => 24],
                    ['color' => 'blue', 'size' => 'l', 'stock' => 20],
                ],
            ],
            [
                'slug' => 'bomber-jacket',
                'sku_prefix' => 'BMJ',
                'name' => 'Bomber Jacket',
                'short_description' => 'Lightweight everyday bomber jacket.',
                'description' => 'A versatile bomber jacket with ribbed cuffs and hem, perfect for layering.',
                'category_slug' => 'men-jackets',
                'gender' => 'men',
                'base_price' => 1450.00,
                'compare_at_price' => 1650.00,
                'badge' => 'new',
                'is_new_arrival' => true,
                'sort_order' => 7,
                'variants' => [
                    ['color' => 'black', 'size' => 'm', 'stock' => 15],
                    ['color' => 'green', 'size' => 'l', 'stock' => 12],
                    ['color' => 'black', 'size' => 'xl', 'stock' => 10],
                ],
            ],
            [
                'slug' => 'relaxed-fit-tee',
                'sku_prefix' => 'RFT',
                'name' => 'Relaxed Fit Tee',
                'short_description' => 'Relaxed silhouette cotton tee.',
                'description' => 'A relaxed-fit cotton tee with a soft drape, great for everyday styling.',
                'category_slug' => 'women-t-shirts',
                'gender' => 'women',
                'base_price' => 340.00,
                'sort_order' => 8,
                'variants' => [
                    ['color' => 'white', 'size' => 's', 'stock' => 30],
                    ['color' => 'red', 'size' => 'm', 'stock' => 25],
                ],
            ],
            [
                'slug' => 'silk-blouse',
                'sku_prefix' => 'SLB',
                'name' => 'Silk Blouse',
                'short_description' => 'Elegant silk-blend blouse.',
                'description' => 'A luxurious silk-blend blouse with a fluid drape, perfect for elevated occasions.',
                'category_slug' => 'women-shirts',
                'gender' => 'women',
                'base_price' => 890.00,
                'compare_at_price' => 990.00,
                'badge' => 'hot',
                'is_featured_drop' => true,
                'sort_order' => 9,
                'variants' => [
                    ['color' => 'beige', 'size' => 's', 'stock' => 14],
                    ['color' => 'black', 'size' => 'm', 'stock' => 16],
                    ['color' => 'black', 'size' => 'l', 'stock' => 10],
                ],
            ],
            [
                'slug' => 'high-waist-trousers',
                'sku_prefix' => 'HWT',
                'name' => 'High-Waist Trousers',
                'short_description' => 'Tailored high-waist trousers.',
                'description' => 'Flattering high-waist tailored trousers with a straight leg finish.',
                'category_slug' => 'women-pants',
                'gender' => 'women',
                'base_price' => 780.00,
                'sort_order' => 10,
                'variants' => [
                    ['color' => 'black', 'size' => 's', 'stock' => 20],
                    ['color' => 'gray', 'size' => 'm', 'stock' => 18],
                ],
            ],
            [
                'slug' => 'wool-blend-coat',
                'sku_prefix' => 'WBC',
                'name' => 'Wool Blend Coat',
                'short_description' => 'Warm wool-blend overcoat.',
                'description' => 'A structured wool-blend coat offering warmth and a polished silhouette.',
                'category_slug' => 'women-jackets',
                'gender' => 'women',
                'base_price' => 1650.00,
                'compare_at_price' => 1900.00,
                'sort_order' => 11,
                'variants' => [
                    ['color' => 'brown', 'size' => 'm', 'stock' => 12],
                    ['color' => 'gray', 'size' => 'l', 'stock' => 10],
                ],
            ],
            [
                'slug' => 'wrap-midi-dress',
                'sku_prefix' => 'WMD',
                'name' => 'Wrap Midi Dress',
                'short_description' => 'Flowing wrap-style midi dress.',
                'description' => 'A flattering wrap-style midi dress with a tie waist and flowing skirt.',
                'category_slug' => 'women-dresses',
                'gender' => 'women',
                'base_price' => 950.00,
                'badge' => 'new',
                'is_new_arrival' => true,
                'sort_order' => 12,
                'variants' => [
                    ['color' => 'red', 'size' => 's', 'stock' => 16],
                    ['color' => 'navy', 'size' => 'm', 'stock' => 14],
                    ['color' => 'navy', 'size' => 'l', 'stock' => 8],
                ],
            ],
        ];
    }

    private function seedCoupon(): void
    {
        Coupon::updateOrCreate(
            ['code' => 'AFIFI10'],
            [
                'type' => 'percent',
                'value' => 10,
                'min_order_total' => null,
                'max_discount' => null,
                'usage_limit' => null,
                'used_count' => 0,
                'starts_at' => null,
                'expires_at' => now()->addYear(),
                'is_active' => true,
            ],
        );
    }

    /**
     * @param  array<string, int>  $productIdsBySlug
     */
    private function seedCampaign(array $productIdsBySlug): void
    {
        $campaign = Campaign::updateOrCreate(
            ['slug' => 'summer-refresh-sale'],
            [
                'name' => 'Summer Refresh Sale',
                'description' => 'Seasonal discounts across select best sellers and new arrivals.',
                'type' => 'seasonal',
                'discount_type' => 'percent',
                'discount_value' => 15,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(30),
                'is_active' => true,
                'banner_media_id' => null,
            ],
        );

        $featuredSlugs = ['classic-crew-t-shirt', 'bomber-jacket', 'silk-blouse', 'wrap-midi-dress'];

        foreach ($featuredSlugs as $index => $slug) {
            $productId = $productIdsBySlug[$slug] ?? null;

            if (! $productId) {
                continue;
            }

            CampaignProduct::updateOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'product_id' => $productId,
                    'product_variant_id' => null,
                ],
                [
                    'campaign_price' => null,
                    'stock_limit' => null,
                    'sold_count' => 0,
                    'sort_order' => $index,
                ],
            );
        }
    }
}
