<?php

namespace App\Applications\Enums;

/**
 * Fixed banner placements (one row per value in DB).
 *
 * top-banner, category-bottom-banner
 */
enum EventBannerName: string
{
    case TOP_BANNER = 'event-banner';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::TOP_BANNER => 'Event banner',
        };
    }
}
