<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NewsTimeline extends Model
{
    protected $fillable = ['news_id', 'created_news_id', 'title', 'details', 'image_path', 'image_caption', 'is_publish', 'date'];

    protected $casts = [
        'date' => 'datetime',
        'is_publish' => 'boolean',
    ];

    protected $appends = ['news_created'];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    /**
     * Article produced by "Make news" from this timeline row (if any).
     */
    public function createdNews(): BelongsTo
    {
        return $this->belongsTo(News::class, 'created_news_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            Tag::class,
            'news_timeline_tag',
            'news_timeline_id',
            'tag_id'
        )->withTimestamps();
    }

    public function getImagePathAttribute($value)
    {
        return UtilsHelper::GetMediaUrl($value);
    }

    public function getNewsCreatedAttribute(): bool
    {
        return $this->created_news_id !== null;
    }
}
