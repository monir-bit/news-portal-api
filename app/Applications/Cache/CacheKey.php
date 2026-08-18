<?php

namespace App\Applications\Cache;

use App\Applications\Helpers\PortalDateHelper;
use Illuminate\Support\Str;

class CacheKey
{
    protected static string $prefix = 'news';
    protected static string $version = 'v1';

    protected static function base(): string
    {
        return static::$prefix . ':' . static::$version;
    }

    public static function header(): string
    {
        return static::base() . ':header:all';
    }

    public static function category(): string
    {
        return static::base() . ':category:all';
    }

    public static function marque(): string
    {
        return static::base() . ':marquee:all';
    }

    public static function breakingNews(): string
    {
        return static::base() . ':breaking:all';
    }

    public static function headerTag(string $tagSlug): string
    {
        return
            static::base() . ':header:tag:' . Str::slug($tagSlug);
    }

    public static function divisions(): string
    {
        return static::base() . ':geo-location:divisions';
    }

    public static function districts($divisionSlug): string
    {
        return static::base() . ':geo-location:districts:'.$divisionSlug;
    }

    public static function upazilas($districtSlug): string
    {
        return static::base() . ':geo-location:upazilas:'.$districtSlug;
    }

    public static function homeSectionWiseNews($sectionName): string
    {
        return static::base() . ':home-section-wise-news:'.$sectionName;
    }

    public static function siteLatestNews(?string $date = null): string
    {
        $date ??= PortalDateHelper::todayDateString();

        return static::base() . ':site-latest-news:' . $date;
    }

    public static function siteMostReadNews(?string $readDate = null): string
    {
        $readDate ??= PortalDateHelper::todayDateString();

        return static::base() . ':site-most-read-news:' . $readDate;
    }

    public static function mostReadNewsByCategory(int $categoryId, int $limit = 15): string
    {
        return static::base() . ':most-read-by-category:' . $categoryId . ':' . $limit;
    }

    public static function newsDetails($slug_key): string
    {
        return static::base() . ':news-details:'.$slug_key;
    }

    public static function newsByCategoryHome($slug): string
    {
        return static::base() . ':news-by-category-home:'.$slug;
    }

    public static function newsByCategory(string $slug, ?string $division = null, ?string $district = null, ?string $upazila = null, $date = null): string
    {
        $key = static::base() . ':news-by-category:' . $slug;
        if ($division !== null && $division !== '') {
            $key .= ':div:' . Str::slug($division);
        }
        if ($district !== null && $district !== '') {
            $key .= ':dist:' . Str::slug($district);
        }
        if ($upazila !== null && $upazila !== '') {
            $key .= ':upa:' . Str::slug($upazila);
        }
        if ($date !== null && $date !== '') {
            $key .= ':date:' . Str::slug($date);
        }

        return $key;
    }

    public static function newsByPrintCategory(string $slug, ?string $date = null): string
    {
        $key = static::base() . ':news-by-print-category:' . $slug;
        if ($date !== null && $date !== '') {
            $key .= ':date:' . Str::slug($date);
        }

        return $key;
    }

    public static function webStorySliderDataHome(): string
    {
        return static::base() . ':web-story-slider-data:home';
    }
    public static function webStorySliderDataSports(): string
    {
        return static::base() . ':web-story-slider-data:sports';
    }

    public static function epaperQuizGrid(string $dateYmd): string
    {
        return static::base() . ':epaper-quiz:grid:' . $dateYmd;
    }

    public static function epaperQuizPage(int $page, string $dateYmd): string
    {
        return static::base() . ':epaper-quiz:page:' . $page . ':' . $dateYmd;
    }

    public static function epaperQuizQuestion(int $questionId): string
    {
        return static::base() . ':epaper-quiz:question:' . $questionId;
    }

    public static function epaperPublications(): string
    {
        return static::base() . ':epaper-reader:publications';
    }

    /**
     * @param  string  $revisionKey  "latest" or concrete revision number as string
     */
    public static function epaperReaderShow(string $slug, string $dateYmd, string $revisionKey): string
    {
        return static::base() . ':epaper-reader:show:' . $slug . ':' . $dateYmd . ':rev:' . $revisionKey;
    }

}
