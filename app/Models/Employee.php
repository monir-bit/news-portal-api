<?php

namespace App\Models;

use App\Support\UtilsHelper;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'id_no',
        'position',
        'full_name',
        'nick_name',
        'designation',
        'mobile_no',
        'desk_no',
        'department',
        'department_position',
        'beat',
        'blood_group',
        'joining_date',
        'present_address',
        'permanent_address',
        'photo',
    ];

    protected $appends = ['photo_url'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'department_position' => 'integer',
            'joining_date' => 'date',
        ];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? UtilsHelper::GetMediaUrl($this->photo) : null;
    }
}
