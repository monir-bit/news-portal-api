<?php

namespace App\Support;

use App\Models\Category;
use App\Repositories\MediaHelperRepositoryInterface;
use Carbon\Carbon;

class UtilsHelper
{
    public static function GetMediaUrl(?string $path, ?string $disk = null): ?string
    {
        if (! $path) {
            return null;
        }

        return app(MediaHelperRepositoryInterface::class)->url($path, $disk);
    }

    /**
     * Upload destination path bucketed by the current year/month, e.g. `uploads/2026/08`.
     */
    public static function MonthYearWisePath(): string
    {
        return 'uploads/'.date('Y').'/'.date('m');
    }

    public static function NewsUrl(Category $category, string $slug): string
    {
        $path = app(CategoryPathService::class)->build($category);

        return '/'.$path.'/'.$slug;
    }

    public static function ToBanglaDate($date): ?string
    {
        if (! $date) {
            return null;
        }

        $date = Carbon::parse($date);

        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $bangla = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

        $months = [
            'January' => 'জানুয়ারি', 'February' => 'ফেব্রুয়ারি', 'March' => 'মার্চ',
            'April' => 'এপ্রিল', 'May' => 'মে', 'June' => 'জুন',
            'July' => 'জুলাই', 'August' => 'আগস্ট', 'September' => 'সেপ্টেম্বর',
            'October' => 'অক্টোবর', 'November' => 'নভেম্বর', 'December' => 'ডিসেম্বর',
        ];

        $day = str_replace($english, $bangla, $date->format('d'));
        $year = str_replace($english, $bangla, $date->format('Y'));
        $month = $months[$date->format('F')] ?? $date->format('F');

        return "{$day} {$month} {$year}";
    }
}
