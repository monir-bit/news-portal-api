<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThankNews extends Model
{
    protected $hidden = [
        'image_path',
    ];

    protected $fillable = [
        'news_id',
        'title',
        'image',
    ];

    /**
     * @return BelongsTo<News, $this>
     */
    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function getImageAttribute(?string $value): ?string
    {
        return UtilsHelper::GetMediaUrl($value);
    }

    /**
     * Raw stored path (`image` column). Use server-side for delete/upload; `image` accessor is the public URL.
     */
    public function getImagePathAttribute(): ?string
    {
        $path = $this->attributes['image'] ?? null;

        return $path !== null && $path !== '' ? (string) $path : null;
    }
}
