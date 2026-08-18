<?php

namespace App\Applications\Helpers;

use App\Models\News;
use App\Models\WebStory;
use App\Repositories\MediaHelperRepositoryInterface;
use App\Services\CategoryPathService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UtilsHelper
{

    public static function IsEnglishVersion():bool
    {
        return app()->getLocale() === "en";
    }

    public static function generateUniqueWebStoryHash(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $maxAttempts = 10;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $hash = '';
            for ($j = 0; $j < 10; $j++) {
                $hash .= $chars[random_int(0, strlen($chars) - 1)];
            }
            if (!WebStory::where('hash_key', $hash)->exists()) {
                return $hash;
            }
        }

        return str_replace('-', '', Str::lower((string) Str::uuid()));
    }

    public static function generateUniqueNewsSlugKey(): string
    {
        do {
            // 12 char random
            $key = Str::lower(Str::random(12));

            // check rules
            $hasLetter = preg_match('/[a-z]/', $key);
            $hasNumber = preg_match('/[0-9]/', $key);

        } while (
            !$hasLetter ||
            !$hasNumber ||
            News::where('slug_key', $key)->exists()
        );

        return $key;
    }


    static public function GetMediaUrl(?string $path, ?string $disk = null): ?string
    {
        $mediaHelperRepository = app(MediaHelperRepositoryInterface::class);
        if (! $path) return null;
        return $mediaHelperRepository->url($path, $disk);
    }


    static function MonthYearWisePath(): string
    {
        return 'uploads/'.date('Y') . '/' . date('m');
    }


    static function SplitCkEditorContent(string $html): array
    {
        return [
            'short' => \Illuminate\Support\Str::limit(
                trim(strip_tags($html)),
                500
            ),
            'rest' => $html
        ];
    }

    /**
     * Convert plain text with line breaks to HTML (ul/li).
     * Each line becomes an <li> item.
     */
    public static function textWithLineBreaksToHtml(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return $text;
        }

        $lines = preg_split('/\r\n|\r|\n/', $text);
        $items = array_map(fn ($line) => '<li>' . htmlspecialchars(trim($line), ENT_QUOTES, 'UTF-8') . '</li>', $lines);

        if (empty(array_filter($lines, fn ($l) => trim($l) !== ''))) {
            return $text;
        }

        return '<ul>' . implode('', $items) . '</ul>';
    }

    /**
     * Convert plain text with line breaks to HTML for news content.
     * Double newline = new paragraph <p>, single newline = <br>.
     */
    public static function plainTextToNewsHtml(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return $text;
        }

        $paragraphs = preg_split('/\r\n\r\n|\n\n|\r\r/', trim($text));
        $html = implode('', array_map(function ($p) {
            $escaped = htmlspecialchars(trim($p), ENT_QUOTES, 'UTF-8');

            return '<p>' . nl2br($escaped) . '</p>';
        }, array_filter($paragraphs, fn ($p) => trim($p) !== '')));

        return $html ?: '<p>' . nl2br(htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8')) . '</p>';
    }

    /**
     * Convert HTML (ul/li, p, br) back to plain text with line breaks.
     * For edit form display.
     */
    public static function htmlToTextWithLineBreaks(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        $trimmed = trim($html);

        // Check if it's ul/li format
        if (preg_match('/<ul[^>]*>(.*?)<\/ul>/is', $trimmed, $ulMatch)) {
            $content = $ulMatch[1];
            if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $content, $liMatches)) {
                $lines = array_map(fn ($li) => trim(strip_tags($li)), $liMatches[1]);

                return implode("\n", $lines);
            }
        }

        // Check for <p> tags
        if (preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $trimmed, $pMatches)) {
            $lines = array_map(function ($p) {
                $content = str_ireplace(['<br>', '<br/>', '<br />'], "\n", $p);

                return trim(strip_tags($content));
            }, $pMatches[1]);

            return implode("\n\n", $lines);
        }

        // nl2br format: replace <br> with newline
        $withNewlines = str_ireplace(['<br>', '<br/>', '<br />'], "\n", $trimmed);

        return trim(strip_tags($withNewlines));
    }

    static function SlugMaker(string $text): string
    {
        // trim spaces
        $text = trim($text);

        // replace multiple spaces with single hyphen
        $text = preg_replace('/\s+/u', '-', $text);

        // remove invalid characters (keep Bangla, English, numbers and hyphen)
        $text = preg_replace('/[^a-zA-Z0-9\p{Bengali}\-]/u', '', $text);

        // remove multiple hyphens
        $text = preg_replace('/-+/', '-', $text);

        // remove hyphen from start/end
        $text = trim($text, '-');

        return $text;
    }


    public static function NewsUrl($category, $slug): string
    {
        $path = app(CategoryPathService::class)->build($category);

        return '/'.$path.'/'.$slug;
    }

    public static function BuildCategoryPath($category, ?Collection $preloadedById = null): string
    {
        $path = app(CategoryPathService::class)->build($category);
        return '/'.$path;
    }

    public static function ToBanglaDate($date)
    {
        if (!$date) return null;

        $date = \Carbon\Carbon::parse($date);

        // English → Bangla number mapping
        $english = ['0','1','2','3','4','5','6','7','8','9'];
        $bangla  = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];

        // English months → Bangla months
        $months = [
            'January' => 'জানুয়ারি',
            'February' => 'ফেব্রুয়ারি',
            'March' => 'মার্চ',
            'April' => 'এপ্রিল',
            'May' => 'মে',
            'June' => 'জুন',
            'July' => 'জুলাই',
            'August' => 'আগস্ট',
            'September' => 'সেপ্টেম্বর',
            'October' => 'অক্টোবর',
            'November' => 'নভেম্বর',
            'December' => 'ডিসেম্বর',
        ];

        // Format parts
        $day   = $date->format('d');
        $month = $date->format('F');
        $year  = $date->format('Y');

        // Convert to Bangla
        $day   = str_replace($english, $bangla, $day);
        $year  = str_replace($english, $bangla, $year);
        $month = $months[$month] ?? $month;

        return "{$day} {$month} {$year}";
    }

    public static function MakeNewsUrl($category, $slug, $base_url = 'https://www.agamirsomoy.com'): string
    {
        $baseUrl = app()->environment('production') ? $base_url : 'http://localhost:3000';
        $path = app(CategoryPathService::class)->build($category);

        return $baseUrl.'/'.$path.'/'.$slug;
    }


}
