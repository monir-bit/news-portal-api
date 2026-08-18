<?php

namespace App\Models;

use App\Models\Concerns\HasMemberImage;
use Illuminate\Database\Eloquent\Model;

class KidsClubMember extends Model
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
        'school_or_madrasa',
        'blood_group',
        'hobby',
        'address',
        'guardian_phone',
        'email',
    ];

    protected function casts(): array
    {
        return [
            'age' => 'integer',
        ];
    }
}
