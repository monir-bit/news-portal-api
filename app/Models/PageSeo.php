<?php

namespace App\Models;

use App\Applications\Enums\PageSeoPageName;
use App\Applications\Helpers\UtilsHelper;
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

    protected $casts = [
        'page_name' => PageSeoPageName::class,
        'keywords' => 'array',
    ];

    public function getOgImageAttribute($value)
    {
        return UtilsHelper::GetMediaUrl($value);
    }
}
