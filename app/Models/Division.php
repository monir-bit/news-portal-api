<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Division extends Model
{
    protected $fillable = ['name', 'slug'];

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function upazilas(): HasManyThrough
    {
        return $this->hasManyThrough(Upazila::class, District::class);
    }
}
