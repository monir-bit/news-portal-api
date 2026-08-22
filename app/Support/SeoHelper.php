<?php

namespace App\Support;

class SeoHelper
{
    /**
     * @param  array<int, string>|null  $keywords
     * @return array<string, mixed>
     */
    public static function Make(
        ?string $title = null,
        ?string $image = null,
        ?string $description = null,
        ?array $keywords = null,
    ): array {
        return [
            'title' => $title ?? '',
            'description' => self::limitAtWordBoundary($description),
            'keywords' => implode(',', $keywords ?? []),
            'og_title' => $title,
            'og_description' => self::limitAtWordBoundary($description),
            'og_image' => $image,
            'og_type' => 'article',
            'og_site_name' => config('app.name'),
            'twitter_card' => 'summary_large_image',
            'twitter_title' => $title,
            'twitter_description' => self::limitAtWordBoundary($description),
            'twitter_image' => $image,
        ];
    }

    private static function limitAtWordBoundary(?string $text, int $limit = 160): string
    {
        $text = trim((string) $text);
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $cut = mb_substr($text, 0, $limit);
        $lastSpace = mb_strrpos($cut, ' ');

        return $lastSpace !== false && $lastSpace > 0 ? mb_substr($cut, 0, $lastSpace) : $cut;
    }
}
