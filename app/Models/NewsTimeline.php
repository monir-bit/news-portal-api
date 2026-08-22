<?php

namespace App\Models;

use App\Support\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsTimeline extends Model
{
    protected $fillable = ['news_id', 'created_news_id', 'title', 'details', 'image_path', 'image_caption', 'is_publish', 'date'];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'is_publish' => 'boolean',
        ];
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function getImagePathAttribute($value): ?string
    {
        return UtilsHelper::GetMediaUrl($value);
    }
}
