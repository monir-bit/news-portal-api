<?php

namespace App\Models;

use App\Support\UtilsHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorldCupQuestion extends Model
{
    protected $fillable = [
        'question',
        'description',
        'image',
        'duration_seconds',
        'sort_order',
        'is_active',
        'start_date_time',
        'end_date_time',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'start_date_time' => 'datetime',
            'end_date_time' => 'datetime',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(WorldCupQuestionOption::class)->orderBy('id');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(WorldCupQuestionParticipation::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function getImageAttribute(?string $value): ?string
    {
        return UtilsHelper::GetMediaUrl($value);
    }

    public function isSubmittableNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->start_date_time !== null) {
            $start = $this->start_date_time instanceof Carbon
                ? $this->start_date_time
                : Carbon::parse((string) $this->start_date_time);
            if ($now->lt($start)) {
                return false;
            }
        }

        if ($this->end_date_time !== null) {
            $end = $this->end_date_time instanceof Carbon
                ? $this->end_date_time
                : Carbon::parse((string) $this->end_date_time);
            if ($now->gt($end)) {
                return false;
            }
        }

        if ($this->relationLoaded('options')) {
            return $this->options->count() >= 2;
        }

        return $this->options()->count() >= 2;
    }

    public function submissionStatus(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        $now = now();

        if ($this->start_date_time !== null) {
            $start = $this->start_date_time instanceof Carbon
                ? $this->start_date_time
                : Carbon::parse((string) $this->start_date_time);
            if ($now->lt($start)) {
                return 'upcoming';
            }
        }

        if ($this->end_date_time !== null) {
            $end = $this->end_date_time instanceof Carbon
                ? $this->end_date_time
                : Carbon::parse((string) $this->end_date_time);
            if ($now->gt($end)) {
                return 'ended';
            }
        }

        return 'open';
    }
}
