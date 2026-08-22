<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorldCupQuestionParticipation extends Model
{
    protected $fillable = [
        'world_cup_question_id',
        'participant_id',
        'world_cup_question_option_id',
        'is_correct',
        'submitted_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(WorldCupQuestion::class, 'world_cup_question_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(WorldCupQuestionOption::class, 'world_cup_question_option_id');
    }
}
