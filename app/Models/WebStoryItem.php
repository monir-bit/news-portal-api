<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebStoryItem extends Model
{
    protected $fillable = [
        'web_story_id',
        'title',
        'image',
        'position',
    ];

    public function webStory(): BelongsTo
    {
        return $this->belongsTo(WebStory::class);
    }

    public function getImageAttribute($value): ?string
    {
        return $value ? UtilsHelper::GetMediaUrl($value) : null;
    }
}
