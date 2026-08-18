<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectionResult extends Model
{
    protected $fillable = [
        'election_seat_id',
        'election_party_id',
        'candidate_name',
        'votes_received',
    ];

    protected $casts = [
        'votes_received' => 'integer',
    ];

    public function seat(): BelongsTo
    {
        return $this->belongsTo(ElectionSeat::class, 'election_seat_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(ElectionParty::class, 'election_party_id');
    }
}
