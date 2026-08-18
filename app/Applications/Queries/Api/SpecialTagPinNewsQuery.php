<?php

namespace App\Applications\Queries\Api;

use App\Models\SpecialTag;

class SpecialTagPinNewsQuery
{
    public function handle()
    {
        return $special_tag_news = SpecialTag::with(
            'news.category.parentRecursive'
        )->whereIn('slug', ['fact-check', 'advice', 'analysis', 'opinion'])->get();
    }
}

