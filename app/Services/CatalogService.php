<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CatalogService
{
    public function createProduct(
        array $attributes,
        array $variants = [],
        array $images = [],
        array $tagIds = [],
        array $collections = []
    ): Product {
        return DB::transaction(function () use ($attributes, $variants, $images, $tagIds, $collections) {
            $product = Product::query()->create($attributes);

            if ($variants !== []) {
                $this->syncProductVariants($product, $variants);
            }

            if ($images !== []) {
                $this->syncProductImages($product, $images);
            }

            if ($tagIds !== []) {
                $this->syncTags($product, $tagIds);
            }

            if ($collections !== []) {
                $this->syncCollections($product, $collections);
            }

            return $product->refresh();
        });
    }

    public function updateProduct(
        Product|int $product,
        array $attributes,
        ?array $variants = null,
        ?array $images = null,
        ?array $tagIds = null,
        ?array $collections = null
    ): Product {
        return DB::transaction(function () use ($product, $attributes, $variants, $images, $tagIds, $collections) {
            $product = $this->resolveProduct($product);
            $product->update($attributes);

            if ($variants !== null) {
                $this->syncProductVariants($product, $variants, true);
            }

            if ($images !== null) {
                $this->syncProductImages($product, $images, true);
            }

            if ($tagIds !== null) {
                $this->syncTags($product, $tagIds);
            }

            if ($collections !== null) {
                $this->syncCollections($product, $collections);
            }

            return $product->refresh();
        });
    }

    public function syncProductVariants(Product|int $product, array $variants, bool $deleteMissing = false): EloquentCollection
    {
        $product = $this->resolveProduct($product);
        $keptIds = [];

        foreach ($variants as $variantData) {
            $variantAttributes = Arr::only($variantData, [
                'color_id',
                'size_id',
                'sku',
                'barcode',
                'price_override',
                'stock',
                'is_active',
            ]);

            if (! empty($variantData['id'])) {
                $variant = $product->variants()->whereKey($variantData['id'])->firstOrFail();
                $variant->update($variantAttributes);
            } else {
                $variant = $product->variants()->create($variantAttributes);
            }

            $keptIds[] = $variant->id;
        }

        if ($deleteMissing) {
            $product->variants()
                ->when($keptIds !== [], fn ($query) => $query->whereNotIn('id', $keptIds))
                ->delete();
        }

        return $product->variants()->get();
    }

    public function syncProductImages(Product|int $product, array $images, bool $deleteMissing = false): EloquentCollection
    {
        $product = $this->resolveProduct($product);
        $keptIds = [];

        foreach ($images as $imageData) {
            $imageAttributes = Arr::only($imageData, [
                'media_id',
                'alt_text',
                'is_primary',
                'display_order',
            ]);

            if (! empty($imageData['id'])) {
                $image = $product->images()->whereKey($imageData['id'])->firstOrFail();
                $image->update($imageAttributes);
            } else {
                $image = $product->images()->create($imageAttributes);
            }

            $keptIds[] = $image->id;
        }

        if ($deleteMissing) {
            $product->images()
                ->when($keptIds !== [], fn ($query) => $query->whereNotIn('id', $keptIds))
                ->delete();
        }

        return $product->images()->orderBy('display_order')->get();
    }

    public function syncTags(Product|int $product, array $tagIds): void
    {
        $this->resolveProduct($product)->tags()->sync($tagIds);
    }

    public function syncCollections(Product|int $product, array $collections): void
    {
        $this->resolveProduct($product)->collections()->sync($this->prepareCollectionSyncPayload($collections));
    }

    public function publishProduct(Product|int $product, mixed $publishedAt = null): Product
    {
        $product = $this->resolveProduct($product);

        $product->update([
            'is_active' => true,
            'published_at' => $publishedAt ?? now(),
        ]);

        return $product->refresh();
    }

    public function unpublishProduct(Product|int $product): Product
    {
        $product = $this->resolveProduct($product);

        $product->update([
            'is_active' => false,
            'published_at' => null,
        ]);

        return $product->refresh();
    }

    public function markProductFlags(
        Product|int $product,
        ?bool $isNewArrival = null,
        ?bool $isBestSeller = null,
        ?bool $isFeaturedDrop = null
    ): Product {
        $product = $this->resolveProduct($product);
        $attributes = [];

        if ($isNewArrival !== null) {
            $attributes['is_new_arrival'] = $isNewArrival;
        }

        if ($isBestSeller !== null) {
            $attributes['is_best_seller'] = $isBestSeller;
        }

        if ($isFeaturedDrop !== null) {
            $attributes['is_featured_drop'] = $isFeaturedDrop;
        }

        if ($attributes !== []) {
            $product->update($attributes);
        }

        return $product->refresh();
    }

    private function prepareCollectionSyncPayload(array $collections): array
    {
        $payload = [];

        foreach ($collections as $key => $collection) {
            if (is_array($collection)) {
                $collectionId = $collection['id'] ?? $key;

                if (! $collectionId) {
                    continue;
                }

                $payload[$collectionId] = [
                    'sort_order' => $collection['sort_order'] ?? 0,
                ];

                continue;
            }

            $payload[$collection] = [
                'sort_order' => 0,
            ];
        }

        return $payload;
    }

    private function resolveProduct(Product|int $product): Product
    {
        if ($product instanceof Product) {
            return $product;
        }

        return Product::query()->findOrFail($product);
    }
}
