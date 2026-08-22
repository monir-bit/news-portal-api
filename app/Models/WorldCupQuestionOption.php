<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorldCupQuestionOption extends Model
{
    protected $fillable = [
        'world_cup_question_id',
        'option_text',
        'is_correct',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(WorldCupQuestion::class, 'world_cup_question_id');
    }
}
