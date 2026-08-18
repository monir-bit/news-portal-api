<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorldCupQuizSet extends Model
{
    protected $fillable = [
        'name',
        'image',
        'slug',
        'is_active',
        'start_time',
        'end_time',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function quizzes(): HasMany
    {
        return $this->hasMany(WorldCupQuiz::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeQuizzes(): HasMany
    {
        return $this->quizzes()->where('is_active', true);
    }

    public function participations(): HasMany
    {
        return $this->hasMany(WorldCupQuizParticipation::class);
    }

    public function scopeActiveNow(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('start_time')
                    ->orWhere('start_time', '<=', now());
            })
            ->where(function (Builder $q): void {
                $q->whereNull('end_time')
                    ->orWhere('end_time', '>=', now());
            });
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
