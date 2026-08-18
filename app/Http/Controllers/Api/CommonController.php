<?php

namespace App\Http\Controllers\Api;

use App\Applications\Queries\Api\BreakingNewsQuery;
use App\Applications\Queries\Api\HeaderNewsQuery;
use App\Applications\Queries\Api\MarqueNewsQuery;
use App\Applications\Queries\Api\RecursiveCategoryQuery;
use App\Applications\Queries\Api\ThankNewsQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CategoryTreeResource;
use Illuminate\Support\Facades\App;

class CommonController extends Controller
{
    public function __construct(
        protected RecursiveCategoryQuery $recursiveCategoryQuery,
    ) {}

    public function common(HeaderNewsQuery $headerNewsQuery, MarqueNewsQuery $marqueNewsQuery, BreakingNewsQuery $breakingNewsQuery, ThankNewsQuery $thankNewsQuery)
    {
//        return Cache::remember('api:common:v2', 60, function () {
            return [
                'thank_news' => $thankNewsQuery->handle(),
                'site_info' => [
                    'name' => 'আগামীর সময়',
                    'description' => 'আগামীর সময় একটি অনলাইন নিউজ পোর্টাল...',
                ],
                'categories' => CategoryTreeResource::collection(
                    $this->recursiveCategoryQuery->handle()
                ),
                'marque_news' => $marqueNewsQuery->handle(),
                'breaking_news' => $breakingNewsQuery->handle(),
                'env' => App::environment()
            ];
//        });

        //
    }



}
