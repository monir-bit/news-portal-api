<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsImage extends Model
{
    protected $fillable = [
        'news_id',
        'image_path',
        'caption',
        'position',
    ];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function getImagePathAttribute($value)
    {
        return UtilsHelper::GetMediaUrl($value);
    }
}
