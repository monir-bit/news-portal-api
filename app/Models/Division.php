<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    protected $fillable = ['name', 'slug'];

    public function districts()
    {
        return $this->hasMany(District::class);
    }

    public function upazilas()
    {
        return $this->hasManyThrough(Upazila::class, District::class);
    }
}
