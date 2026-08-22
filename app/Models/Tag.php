<?php

namespace App\Models;

use App\Support\UtilsHelper;
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

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
        ];
    }

    public function getOgImageAttribute($value): ?string
    {
        return UtilsHelper::GetMediaUrl($value);
    }

    public function news(): BelongsToMany
    {
        return $this->belongsToMany(
            News::class,
            'news_tag_mappings',
            'tag_id',
            'news_id'
        );
    }
}
