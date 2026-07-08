<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'brand_id',
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'gender',
        'badge',
        'base_price',
        'compare_at_price',
        'has_variants',
        'is_active',
        'is_new_arrival',
        'is_best_seller',
        'is_featured_drop',
        'meta_title',
        'meta_description',
        'published_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'has_variants' => 'boolean',
            'is_active' => 'boolean',
            'is_new_arrival' => 'boolean',
            'is_best_seller' => 'boolean',
            'is_featured_drop' => 'boolean',
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function sizeGuides(): BelongsToMany
    {
        return $this->belongsToMany(SizeGuide::class, 'product_size_guide');
    }

    public function campaignProducts(): HasMany
    {
        return $this->hasMany(CampaignProduct::class);
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_products')
            ->withPivot('product_variant_id', 'campaign_price', 'stock_limit', 'sold_count', 'sort_order')
            ->withTimestamps();
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function searchLogs(): HasMany
    {
        return $this->hasMany(SearchLog::class, 'clicked_product_id');
    }

    public function homepageSectionProducts(): HasMany
    {
        return $this->hasMany(HomepageSectionProduct::class);
    }

    public function homepageSections(): BelongsToMany
    {
        return $this->belongsToMany(HomepageSection::class, 'homepage_section_products')
            ->withPivot('sort_order');
    }
}
