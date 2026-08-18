<?php

namespace App\Models;

use App\Models\Concerns\HasMemberImage;
use Illuminate\Database\Eloquent\Model;

class CareerClubMember extends Model
{
    use HasMemberImage;

    protected $hidden = [
        'image_path',
    ];

    protected $fillable = [
        'name',
        'age',
        'image',
        'gender',
        'educational_qualification',
        'preferred_profession',
        'work_experience',
        'address',
        'phone',
        'email',
    ];

    protected function casts(): array
    {
        return [
            'age' => 'integer',
        ];
    }
}
