<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorldCupMatch extends Model
{
    protected $fillable = [
        'team_a',
        'team_b',
        'team_a_score',
        'team_b_score',
        'team_a_penalty_score',
        'team_b_penalty_score',
        'match_date',
        'start_time',
        'venue',
        'title',
        'season',
        'stage',
        'group_name',
        'status',
        'news_id',
    ];

    protected function casts(): array
    {
        return [
            'team_a_score' => 'integer',
            'team_b_score' => 'integer',
            'team_a_penalty_score' => 'integer',
            'team_b_penalty_score' => 'integer',
            'match_date' => 'date:Y-m-d',
        ];
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(WorldCupTeam::class, 'team_a');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(WorldCupTeam::class, 'team_b');
    }

    public function commentaries(): HasMany
    {
        return $this->hasMany(WorldCupMatchCommentary::class, 'match_id');
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class, 'news_id');
    }

    public function timeLines(): HasMany
    {
        return $this->hasMany(NewsTimeline::class, 'news_id', 'news_id');
    }

    public function scopeForTodayWindow(Builder $query): Builder
    {
        $from = now()->subHours(24);
        $to = now()->addDays(10)->endOfDay();

        return $query
            ->whereIn('status', ['scheduled', 'live'])
            ->whereRaw(
                '(match_date + start_time) BETWEEN ? AND ?',
                [$from, $to]
            )->limit(6);
    }
}
