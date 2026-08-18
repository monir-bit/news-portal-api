<?php

namespace App\Models;

use App\Applications\Enums\EventBannerName;
use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;

class EventBanner extends Model
{
    protected $fillable = [
        'banner_name',
        'mobile_image',
        'desktop_image',
        'link',
        'start_date',
        'end_date',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'banner_name' => EventBannerName::class,
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function getMobileImageAttribute(?string $value): ?string
    {
        return UtilsHelper::GetMediaUrl($value);
    }

    public function getDesktopImageAttribute(?string $value): ?string
    {
        return UtilsHelper::GetMediaUrl($value);
    }

    public function getMobileImagePathAttribute(): ?string
    {
        $path = $this->attributes['mobile_image'] ?? null;

        return $path !== null && $path !== '' ? (string) $path : null;
    }

    public function getDesktopImagePathAttribute(): ?string
    {
        $path = $this->attributes['desktop_image'] ?? null;

        return $path !== null && $path !== '' ? (string) $path : null;
    }
}
