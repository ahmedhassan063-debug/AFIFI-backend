<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'product_id',
        'product_variant_id',
        'campaign_price',
        'stock_limit',
        'sold_count',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'campaign_price' => 'decimal:2',
            'stock_limit' => 'integer',
            'sold_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
