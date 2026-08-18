<?php

namespace App\Models;

use App\Casts\DateOnlyCast;
use App\Support\EpaperApiCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EpaperQuestion extends Model
{
    protected $fillable = [
        'epaper_category_id',
        'page_number',
        'title',
        'publish_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'publish_date' => DateOnlyCast::class,
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EpaperCategory::class, 'epaper_category_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(EpaperQuestionOption::class, 'epaper_question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(EpaperQuestionAnswer::class, 'epaper_question_id');
    }

    protected static function booted(): void
    {
        static::saved(function (EpaperQuestion $question): void {
            EpaperApiCache::forgetQuestionSlots($question);
        });

        static::deleted(function (EpaperQuestion $question): void {
            EpaperApiCache::forgetQuestionSlots($question);
        });
    }
}
