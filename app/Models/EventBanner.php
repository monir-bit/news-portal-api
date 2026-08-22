<?php

namespace App\Models;

use App\Enums\EventBannerName;
use App\Support\UtilsHelper;
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
}
