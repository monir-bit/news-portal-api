<?php

namespace App\Http\Controllers;

use App\Models\PageSeo;
use App\Support\SeoHelper;

class PageSeoController extends Controller
{
    /**
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
