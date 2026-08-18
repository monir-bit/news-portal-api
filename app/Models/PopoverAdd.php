<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;

class PopoverAdd extends Model
{
    protected $table = 'popover_adds';

    protected $hidden = [
        'image_path',
    ];

    protected $fillable = [
        'title',
        'image',
        'start_time',
        'link',
        'end_time',
        'delay',
        'duration',
        'is_active',
        'width',
        'height',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function getImageAttribute(?string $value): ?string
    {
        return UtilsHelper::GetMediaUrl($value);
    }

    /**
     * Raw stored path (`image` column). Server-side uploads/deletes should use this.
     */
    public function getImagePathAttribute(): ?string
    {
        $path = $this->attributes['image'] ?? null;

        return $path !== null && $path !== '' ? (string) $path : null;
    }
}
