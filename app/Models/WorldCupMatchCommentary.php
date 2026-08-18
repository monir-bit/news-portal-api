<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorldCupMatchCommentary extends Model
{
    protected $fillable = [
        'match_id',
        'description',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(WorldCupMatch::class, 'match_id');
    }
}
