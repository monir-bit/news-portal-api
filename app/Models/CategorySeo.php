<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategorySeo extends Model
{
    protected $fillable = [
        'category_id',
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
        'keywords' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getOgImageAttribute($value)
    {
        return UtilsHelper::GetMediaUrl($value);
    }
}
