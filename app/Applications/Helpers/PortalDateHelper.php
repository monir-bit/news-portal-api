<?php

namespace App\Applications\Helpers;

use Carbon\Carbon;

class PortalDateHelper
{
    public const TIMEZONE = 'Asia/Dhaka';

    public static function todayDateString(): string
    {
        return Carbon::now(self::TIMEZONE)->toDateString();
    }

    public static function todayStart(): Carbon
    {
        return Carbon::now(self::TIMEZONE)->startOfDay();
    }

    public static function todayEnd(): Carbon
    {
        return Carbon::now(self::TIMEZONE)->endOfDay();
    }

    public static function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    public static function subDay(): Carbon
    {
        return Carbon::now(self::TIMEZONE)->subDay();
    }
}
