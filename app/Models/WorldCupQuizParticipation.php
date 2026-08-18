<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorldCupQuizParticipation extends Model
{
    protected $fillable = [
        'world_cup_quiz_set_id',
        'participant_id',
        'score',
        'total_questions',
        'started_at',
        'completed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'score' => 'integer',
        'total_questions' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function set(): BelongsTo
    {
        return $this->belongsTo(WorldCupQuizSet::class, 'world_cup_quiz_set_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(WorldCupQuizAnswer::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
