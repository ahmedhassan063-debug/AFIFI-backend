<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AboutContent extends Model
{
    use HasFactory;

    const CREATED_AT = null;

    protected $fillable = [
        'story_media_id',
        'story_title',
        'story_body',
    ];

    public function storyMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'story_media_id');
    }
}
