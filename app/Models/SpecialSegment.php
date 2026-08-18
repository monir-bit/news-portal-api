<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;

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

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the tag that belongs to the special segment
     */
    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }

    /**
     * Get all news in this special segment
     */
    public function news()
    {
        return $this->belongsToMany(
            News::class,
            'special_segment_news',
            'special_segment_id',
            'news_id'
        )->withPivot('position')->withTimestamps();
    }

    public function getDesktopBannerImageAttribute($value)
    {
        return UtilsHelper::GetMediaUrl($value);
    }
    public function getMobileBannerImageAttribute($value)
    {
        return UtilsHelper::GetMediaUrl($value);
    }
}
