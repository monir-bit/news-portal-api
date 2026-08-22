<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryTreeResource;
use App\Services\News\BreakingNewsQuery;
use App\Services\News\HeaderNewsQuery;
use App\Services\News\MarqueNewsQuery;
use App\Services\News\RecursiveCategoryQuery;
use App\Services\News\ThankNewsQuery;
use Illuminate\Support\Facades\App;

class CommonController extends Controller
{
    public function __construct(
        protected RecursiveCategoryQuery $recursiveCategoryQuery,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function common(
        HeaderNewsQuery $headerNewsQuery,
        MarqueNewsQuery $marqueNewsQuery,
        BreakingNewsQuery $breakingNewsQuery,
        ThankNewsQuery $thankNewsQuery,
    ): array {
        return [
            'thank_news' => $thankNewsQuery->handle(),
            'site_info' => [
                'name' => 'আগামীর সময়',
                'description' => 'আগামীর সময় একটি অনলাইন নিউজ পোর্টাল...',
            ],
            'categories' => CategoryTreeResource::collection(
                $this->recursiveCategoryQuery->handle()
            ),
            'marque_news' => $marqueNewsQuery->handle(),
            'breaking_news' => $breakingNewsQuery->handle(),
            'env' => App::environment(),
        ];
    }
}
