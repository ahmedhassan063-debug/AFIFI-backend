<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductMediaSeeder extends Seeder
{
    /**
     * Additive, idempotent seeder that guarantees every product has at least
     * one primary image so GET /api/catalog/products returns non-empty
     * `images`. Products that already have images (real photos or otherwise)
     * are left completely untouched.
     *
     * Uses the existing AFIFI brand placeholder artwork as a temporary,
     * per-product image until real product photography is available.
     */
    public function run(): void
    {
        $placeholderSource = __DIR__.'/assets/afifi-placeholder.svg';

        if (! File::exists($placeholderSource)) {
            $this->command?->warn('AFIFI placeholder asset missing, skipping ProductMediaSeeder.');

            return;
        }

        $placeholderContents = File::get($placeholderSource);

        Product::query()
            ->whereDoesntHave('images')
            ->orderBy('id')
            ->each(function (Product $product) use ($placeholderContents): void {
                $this->attachPlaceholderImage($product, $placeholderContents);
            });
    }

    private function attachPlaceholderImage(Product $product, string $placeholderContents): void
    {
        $directory = "products/{$product->slug}";
        $filename = "{$product->slug}.svg";
        $path = "{$directory}/{$filename}";

        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, $placeholderContents);
        }

        $media = Media::query()->firstOrCreate(
            ['path' => $path],
            [
                'uuid' => (string) Str::uuid(),
                'disk' => 'public',
                'directory' => $directory,
                'filename' => $filename,
                'mime_type' => 'image/svg+xml',
                'size_bytes' => Storage::disk('public')->size($path),
                'width' => 1400,
                'height' => 500,
                'alt_text' => $product->name,
                'title' => "{$product->name} (placeholder)",
                'uploaded_by' => null,
            ],
        );

        ProductImage::query()->firstOrCreate(
            [
                'product_id' => $product->id,
                'media_id' => $media->id,
            ],
            [
                'alt_text' => $product->name,
                'is_primary' => true,
                'display_order' => 0,
            ],
        );
    }
}
