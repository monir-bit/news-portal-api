<?php

namespace App\Applications\Helpers;

class SeoHelper
{
    public static function Make(
        ?string $title = null,
        ?string $image = null,
        ?string $description = null,
        ?array $keywords = null,
    ): array {
        return [
            'title' => $title ?? '',
            'description' => self::limitAtWordBoundary($description) ?? '',
            'keywords' => implode(',', $keywords ?? []) ?? '',
            // Open Graph (Facebook, LinkedIn)
            'og_title' => $title,
            'og_description' => self::limitAtWordBoundary($description) ?? '',
            'og_image' => $image,
            'og_type' => 'article',
            'og_site_name' => config('app.name'),
            // Twitter Card
            'twitter_card' => 'summary_large_image',
            'twitter_title' => $title,
            'twitter_description' =>  self::limitAtWordBoundary($description) ?? '',
            'twitter_image' => $image,
        ];
    }

    static function limitAtWordBoundary(string | null $text, int $limit = 160): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $limit) {
            return $text;
        }
        $cut = mb_substr($text, 0, $limit);
        $lastSpace = mb_strrpos($cut, ' ');
        if ($lastSpace !== false && $lastSpace > 0) {
            return mb_substr($cut, 0, $lastSpace);
        }
        return $cut;
    }
}
