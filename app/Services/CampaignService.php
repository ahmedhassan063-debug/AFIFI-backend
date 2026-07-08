<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignProduct;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CampaignService
{
    public function getActiveCampaigns(): Collection
    {
        return Campaign::query()
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->with(['bannerMedia', 'campaignProducts'])
            ->orderBy('starts_at')
            ->get();
    }

    public function resolveCampaignPrice(Product|int $product, ProductVariant|int|null $productVariant = null): ?float
    {
        $campaignProduct = $this->findActiveCampaignProduct($product, $productVariant);

        if (! $campaignProduct) {
            return null;
        }

        if ($campaignProduct->campaign_price !== null) {
            return (float) $campaignProduct->campaign_price;
        }

        $variant = $productVariant !== null ? $this->resolveProductVariant($productVariant) : null;
        $basePrice = $variant !== null && $variant->price_override !== null
            ? (float) $variant->price_override
            : (float) $this->resolveProduct($product)->base_price;

        return $this->applyCampaignDiscount($campaignProduct->campaign, $basePrice);
    }

    public function checkCampaignStockLimit(CampaignProduct|int $campaignProduct, int $quantity = 1): bool
    {
        $campaignProduct = $this->resolveCampaignProduct($campaignProduct);

        if ($campaignProduct->stock_limit === null) {
            return true;
        }

        return ($campaignProduct->sold_count + $quantity) <= $campaignProduct->stock_limit;
    }

    public function incrementSoldCount(CampaignProduct|int $campaignProduct, int $quantity = 1): CampaignProduct
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Sold quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($campaignProduct, $quantity) {
            $campaignProduct = $this->lockCampaignProduct($campaignProduct);

            if (! $this->checkCampaignStockLimit($campaignProduct, $quantity)) {
                throw new RuntimeException('Campaign stock limit has been reached.');
            }

            $campaignProduct->increment('sold_count', $quantity);

            return $campaignProduct->refresh();
        });
    }

    private function findActiveCampaignProduct(Product|int $product, ProductVariant|int|null $productVariant = null): ?CampaignProduct
    {
        $product = $this->resolveProduct($product);
        $variantId = $productVariant instanceof ProductVariant
            ? $productVariant->id
            : $productVariant;

        return CampaignProduct::query()
            ->where('product_id', $product->id)
            ->where(function ($query) use ($variantId) {
                if ($variantId !== null) {
                    $query->where('product_variant_id', $variantId)
                        ->orWhereNull('product_variant_id');

                    return;
                }

                $query->whereNull('product_variant_id');
            })
            ->whereHas('campaign', function ($query) {
                $query->where('is_active', true)
                    ->where('starts_at', '<=', now())
                    ->where('ends_at', '>=', now());
            })
            ->with('campaign')
            ->orderByRaw('product_variant_id is null')
            ->orderBy('campaign_price')
            ->first();
    }

    private function applyCampaignDiscount(Campaign $campaign, float $price): float
    {
        $discountedPrice = match ($campaign->discount_type) {
            'percent' => $price - ($price * ((float) $campaign->discount_value / 100)),
            'fixed' => $price - (float) $campaign->discount_value,
            default => $price,
        };

        return round(max(0, $discountedPrice), 2);
    }

    private function resolveProduct(Product|int $product): Product
    {
        if ($product instanceof Product) {
            return $product;
        }

        return Product::query()->findOrFail($product);
    }

    private function resolveCampaignProduct(CampaignProduct|int $campaignProduct): CampaignProduct
    {
        if ($campaignProduct instanceof CampaignProduct) {
            return $campaignProduct;
        }

        return CampaignProduct::query()->findOrFail($campaignProduct);
    }

    private function resolveProductVariant(ProductVariant|int $productVariant): ProductVariant
    {
        if ($productVariant instanceof ProductVariant) {
            return $productVariant;
        }

        return ProductVariant::query()->findOrFail($productVariant);
    }

    private function lockCampaignProduct(CampaignProduct|int $campaignProduct): CampaignProduct
    {
        $campaignProductId = $campaignProduct instanceof CampaignProduct ? $campaignProduct->id : $campaignProduct;

        return CampaignProduct::query()
            ->whereKey($campaignProductId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
