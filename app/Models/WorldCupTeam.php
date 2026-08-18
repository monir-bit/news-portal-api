<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorldCupTeam extends Model
{
    protected $fillable = [
        'name',
        'name_normalised',
        'continent',
        'flag_icon',
        'flag_unicode',
        'fifa_code',
        'group',
        'confed',
    ];

    public function homeMatches(): HasMany
    {
        return $this->hasMany(WorldCupMatch::class, 'team_a');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(WorldCupMatch::class, 'team_b');
    }
}
