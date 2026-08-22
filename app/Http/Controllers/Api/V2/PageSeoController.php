<?php

namespace App\Http\Controllers\Api\V2;

use App\Applications\Helpers\SeoHelper;
use App\Http\Controllers\Controller;
use App\Models\PageSeo;

class PageSeoController extends Controller
{
    /**
     * SEO meta for a static page (terms, about, contact, privacy, ...).
     *
     * PHPDoc note: `sort_description` is not an attribute on `PageSeo` (the column is
     * `description`); this mirrors a v1 quirk where the description is always empty.
     * Preserved as-is per the "same behavior" requirement of the v2 rebuild.
     *
     * @return array<string, mixed>
     */
    public function get(string $name): array
    {
        $seo = PageSeo::where('page_name', $name)->firstOrFail();

        return SeoHelper::Make(
            title: $seo->title,
            image: $seo->og_image,
            description: $seo->sort_description,
            keywords: $seo->keywords,
        );
    }
}
