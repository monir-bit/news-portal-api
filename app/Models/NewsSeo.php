<?php

namespace App\Models;

use App\Support\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsSeo extends Model
{
    protected $fillable = [
        'news_id',
        'title',
        'description',
        'keywords',
        'og_title',
        'og_description',
        'og_image',
        'canonical_url',
        'robots',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
        ];
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function getOgImageAttribute($value): ?string
    {
        return UtilsHelper::GetMediaUrl($value);
    }
}
