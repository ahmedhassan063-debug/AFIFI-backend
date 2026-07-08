<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'uuid',
        'disk',
        'directory',
        'filename',
        'path',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'alt_text',
        'title',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class, 'logo_media_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'image_media_id');
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'image_media_id');
    }

    public function productImages(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'banner_media_id');
    }

    public function homepageSections(): HasMany
    {
        return $this->hasMany(HomepageSection::class);
    }

    public function desktopBanners(): HasMany
    {
        return $this->hasMany(Banner::class, 'desktop_media_id');
    }

    public function mobileBanners(): HasMany
    {
        return $this->hasMany(Banner::class, 'mobile_media_id');
    }

    public function aboutContents(): HasMany
    {
        return $this->hasMany(AboutContent::class, 'story_media_id');
    }
}
