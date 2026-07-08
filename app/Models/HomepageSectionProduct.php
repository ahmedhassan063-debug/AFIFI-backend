<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageSectionProduct extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'homepage_section_id',
        'product_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function homepageSection(): BelongsTo
    {
        return $this->belongsTo(HomepageSection::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
