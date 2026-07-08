<?php

namespace App\Services;

use App\Models\AboutContent;
use App\Models\AboutStat;
use App\Models\AboutValue;
use App\Models\Banner;
use App\Models\FaqItem;
use App\Models\HomepageSection;
use App\Models\PolicyPage;
use App\Models\TrustStripItem;
use Illuminate\Database\Eloquent\Collection;

class CmsService
{
    public function getActiveHomepageSections(): Collection
    {
        return HomepageSection::query()
            ->where('is_active', true)
            ->with(['media', 'sectionProducts.product'])
            ->orderBy('sort_order')
            ->get();
    }

    public function getTrustStripItems(): Collection
    {
        return TrustStripItem::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function getActiveBannersByPlacement(string $placement = 'hero'): Collection
    {
        return Banner::query()
            ->where('placement', $placement)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->with(['desktopMedia', 'mobileMedia'])
            ->orderBy('priority')
            ->get();
    }

    public function getAboutPageData(): array
    {
        return [
            'content' => AboutContent::query()
                ->with('storyMedia')
                ->first(),
            'values' => AboutValue::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'stats' => AboutStat::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
        ];
    }

    public function getFaqItems(): Collection
    {
        return FaqItem::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function getPolicyPageBySlug(string $slug): ?PolicyPage
    {
        return PolicyPage::query()
            ->where('slug', $slug)
            ->first();
    }
}
