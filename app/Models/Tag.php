<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'title',
        'description',
        'keywords',
        'og_title',
        'og_description',
        'og_image',
        'canonical_url',
        'robots',
    ];

    protected $casts = [
        'keywords' => 'array',
    ];

    public function getOgImageAttribute($value)
    {
        return UtilsHelper::GetMediaUrl($value);
    }

    public function news()
    {
        return $this->belongsToMany(
            News::class,
            'news_tag_mappings',
            'tag_id',
            'news_id'
        );
    }

    public function newsTimelines(): BelongsToMany
    {
        return $this->belongsToMany(
            NewsTimeline::class,
            'news_timeline_tag',
            'tag_id',
            'news_timeline_id'
        )->withTimestamps();
    }
}
