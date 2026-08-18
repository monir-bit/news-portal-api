<?php

namespace App\Http\Controllers\Api;

use App\Applications\Helpers\SeoHelper;
use App\Http\Controllers\Controller;
use App\Models\PageSeo;

class PageSeoController extends Controller
{
    public function get($name){
        $seo = PageSeo::where('page_name', $name)->firstOrFail();
        return SeoHelper::Make(
            title: $seo->title,
            image: $seo->og_image,
            description: $seo->sort_description,
            keywords: $seo->keywords,
        );
    }
}
