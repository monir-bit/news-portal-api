<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebStory extends Model
{
    protected $fillable = [
        'news_id',
        'hash_key',
    ];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WebStoryItem::class)->orderBy('position');
    }
}
