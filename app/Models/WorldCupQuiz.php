<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorldCupQuiz extends Model
{
    protected $fillable = [
        'world_cup_quiz_set_id',
        'question',
        'description',
        'image',
        'duration_seconds',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function set(): BelongsTo
    {
        return $this->belongsTo(WorldCupQuizSet::class, 'world_cup_quiz_set_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(WorldCupQuizOption::class)->orderBy('id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(WorldCupQuizAnswer::class);
    }

    public function getImageAttribute(?string $value): ?string
    {
        return UtilsHelper::GetMediaUrl($value);
    }

    public function getImagePathAttribute(): ?string
    {
        $path = $this->attributes['image'] ?? null;

        return $path !== null && $path !== '' ? (string) $path : null;
    }
}
