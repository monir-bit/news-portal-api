<?php

namespace App\Models;

use App\Models\Concerns\HasMemberImage;
use Illuminate\Database\Eloquent\Model;

class GoldClubMember extends Model
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
        'profession',
        'blood_group',
        'hobby',
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
