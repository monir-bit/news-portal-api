<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorldCupQuizAnswer extends Model
{
    protected $fillable = [
        'world_cup_quiz_participation_id',
        'world_cup_quiz_id',
        'world_cup_quiz_option_id',
        'is_correct',
        'timed_out',
        'answered_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'timed_out' => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function participation(): BelongsTo
    {
        return $this->belongsTo(WorldCupQuizParticipation::class, 'world_cup_quiz_participation_id');
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(WorldCupQuiz::class, 'world_cup_quiz_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(WorldCupQuizOption::class, 'world_cup_quiz_option_id');
    }
}
