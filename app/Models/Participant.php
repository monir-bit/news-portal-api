<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'date_of_birth',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuestionAnswer::class);
    }

    public function worldCupQuestionParticipations(): HasMany
    {
        return $this->hasMany(WorldCupQuestionParticipation::class);
    }

    public function worldCupQuizParticipations(): HasMany
    {
        return $this->hasMany(WorldCupQuizParticipation::class);
    }
}
