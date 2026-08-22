<?php

namespace App\Models;

use App\Support\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThankNews extends Model
{
    protected $table = 'thank_news';

    protected $hidden = [
        'image_path',
    ];

    protected $fillable = [
        'news_id',
        'title',
        'image',
    ];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function getImageAttribute(?string $value): ?string
    {
        return UtilsHelper::GetMediaUrl($value);
    }
}
