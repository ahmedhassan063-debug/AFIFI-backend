<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SizeGuideRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'size_guide_id',
        'size_id',
        'measurement_type',
        'value_cm',
    ];

    protected function casts(): array
    {
        return [
            'value_cm' => 'decimal:2',
        ];
    }

    public function sizeGuide(): BelongsTo
    {
        return $this->belongsTo(SizeGuide::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
