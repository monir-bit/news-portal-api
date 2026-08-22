<?php

namespace App\Models;

use App\Support\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends Model
{
    protected $fillable = [
        'name',
        'english_name',
        'slug',
        'designation',
        'bio',
        'facebook',
        'email',
        'linkedin_url',
        'image',
    ];

    public function getImageAttribute($value): ?string
    {
        return $value ? UtilsHelper::GetMediaUrl($value) : null;
    }

    public function news(): BelongsToMany
    {
        return $this->belongsToMany(
            News::class,
            'author_news_mappings',
            'author_id',
            'news_id'
        );
    }
}
