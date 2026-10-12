<?php

namespace App\Models;

use App\Support\UtilsHelper;
use Illuminate\Database\Eloquent\Model;

class LayoutSectionPin extends Model
{
    protected $fillable = ['layout_section_id', 'icon_image', 'name', 'position'];

    protected $appends = ['icon_image_url'];

    protected $casts = [
        'position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function layoutSection()
    {
        return $this->belongsTo(LayoutSection::class, 'layout_section_id', 'id');
    }

    public function getIconImageUrlAttribute(): ?string
    {
        return UtilsHelper::GetMediaUrl($this->icon_image);
    }
}
