<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;

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

    protected static function booted(): void
    {
        static::saving(function (Author $author) {
            if ($author->english_name) {
                $author->slug = \Illuminate\Support\Str::slug($author->english_name);
            }
        });
    }

    public function getImageAttribute($value): ?string
    {
        return $value ? UtilsHelper::GetMediaUrl($value) : null;
    }

    public function news()
    {
        return $this->belongsToMany(
            News::class,
            'author_news_mappings',
            'author_id',
            'news_id'
        );
    }
}
