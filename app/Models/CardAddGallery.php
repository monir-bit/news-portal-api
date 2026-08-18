<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;

class CardAddGallery extends Model
{
    protected $fillable = ['name', 'image', 'is_active'];

    protected $appends = ['image_url'];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return UtilsHelper::GetMediaUrl($this->image);
    }
}
