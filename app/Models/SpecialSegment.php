<?php

namespace App\Models;

use App\Support\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SpecialSegment extends Model
{
    protected $fillable = [
        'title',
        'tag_id',
        'slug',
        'desktop_banner_image',
        'mobile_banner_image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    public function news(): BelongsToMany
    {
        return $this->belongsToMany(
            News::class,
            'special_segment_news',
            'special_segment_id',
            'news_id'
        )->withPivot('position')->withTimestamps();
    }

    public function getDesktopBannerImageAttribute($value): ?string
    {
        return UtilsHelper::GetMediaUrl($value);
    }

    public function getMobileBannerImageAttribute($value): ?string
    {
        return UtilsHelper::GetMediaUrl($value);
    }
}
