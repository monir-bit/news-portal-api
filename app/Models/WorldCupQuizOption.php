<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorldCupQuizOption extends Model
{
    protected $fillable = [
        'world_cup_quiz_id',
        'option_text',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(WorldCupQuiz::class, 'world_cup_quiz_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(WorldCupQuizAnswer::class);
    }
}
