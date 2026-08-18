<?php

namespace App\Applications\Queries\Api;

use App\Models\RamadanSchedule;
use Illuminate\Support\Collection;

class RamadanScheduleQuery
{
    public function handle(): collection
    {
        $results = RamadanSchedule::orderBy('date')->get();
        return $results;
    }
}

