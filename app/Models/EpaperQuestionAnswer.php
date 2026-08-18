<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EpaperQuestionAnswer extends Model
{
    protected $fillable = [
        'epaper_question_id',
        'participant_id',
        'epaper_question_option_id',
        'is_correct',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(EpaperQuestion::class, 'epaper_question_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(EpaperQuestionOption::class, 'epaper_question_option_id');
    }
}
