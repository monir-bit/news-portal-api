<?php

namespace App\Models;

use App\Enums\PageSeoPageName;
use App\Support\UtilsHelper;
use Illuminate\Database\Eloquent\Model;

class PageSeo extends Model
{
    protected $fillable = [
        'page_name',
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
            'page_name' => PageSeoPageName::class,
            'keywords' => 'array',
        ];
    }

    public function getOgImageAttribute($value): ?string
    {
        return UtilsHelper::GetMediaUrl($value);
    }
}
