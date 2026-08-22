<?php

namespace App\Applications\Queries\Api;

use App\Models\SpecialTag;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class SpecialTagPinNewsQuery
{
    public function handle()
    {
        return app(SharedCache::class)->flexible(CacheKey::make('special-tag-pin'), [CacheTags::section('special-tag-pin')], function () {
            return SpecialTag::with(
                'news.category.parentRecursive'
            )->whereIn('slug', ['fact-check', 'advice', 'analysis', 'opinion'])->get();
        }, [300, 900]);
    }
}
