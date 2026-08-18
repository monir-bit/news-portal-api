<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;

class MediaGallery extends Model
{
    protected $fillable = ['name', 'media_type', 'path'];

    protected $appends = ['image_url'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return UtilsHelper::GetMediaUrl($this->path);
    }
}
