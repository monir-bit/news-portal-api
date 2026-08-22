<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorldCupQuizSet extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }
}
