<?php

use App\Http\Controllers\Api\V2\ClubMemberController;
use App\Http\Controllers\Api\V2\CommonController;
use App\Http\Controllers\Api\V2\EmployeeController;
use App\Http\Controllers\Api\V2\EventBannerController;
use App\Http\Controllers\Api\V2\GeoLocationController;
use App\Http\Controllers\Api\V2\PageController;
use App\Http\Controllers\Api\V2\PageSeoController;
use App\Http\Controllers\Api\V2\PopoverAddController;
use App\Http\Controllers\Api\V2\SitemapController;
use App\Http\Controllers\Api\V2\WebStoryController;
use Illuminate\Support\Facades\Route;

// --- Static/misc content: pages, seo, sitemap, popover, banners, club members,
// web stories, employees, geo-location, common ---

Route::post('/club/gold', [ClubMemberController::class, 'storeGold'])->middleware('throttle:public-forms');
Route::post('/club/kids', [ClubMemberController::class, 'storeKids'])->middleware('throttle:public-forms');
Route::post('/club/career', [ClubMemberController::class, 'storeCareer'])->middleware('throttle:public-forms');

Route::get('/common', [CommonController::class, 'common']);
Route::get('/popover-add/active', [PopoverAddController::class, 'active']);
Route::get('/event-banner/{name}', [EventBannerController::class, 'show']);

Route::get('/divisions', [GeoLocationController::class, 'divisions']);
Route::get('/districts/{divisionSlug}', [GeoLocationController::class, 'districts']);
Route::get('/upazilas/{districtSlug}', [GeoLocationController::class, 'upazilas']);

Route::get('/web-story-slider-data', [WebStoryController::class, 'sliderData']);
Route::get('/sports-web-story-slider-data', [WebStoryController::class, 'sportsWebHistory']);
Route::get('/web-story-details/{hash_key}', [WebStoryController::class, 'sliderDetails']);

Route::get('/employees', [EmployeeController::class, 'index']);

// Static pages (terms, about, contact, privacy)
Route::get('/page/{name}', [PageController::class, 'show']);
Route::get('/pages', [PageController::class, 'index']);
Route::get('/page-seo/{name}', [PageSeoController::class, 'get']);

// Sitemap data feeds — not routed at all in v1 (SitemapController/SitemapService
// exist there but have no route registration in routes/api.php or routes/web.php).
// Routes below are new, added for parity with the ported controller logic; there
// is no v1 path to mirror.
Route::get('/sitemap', [SitemapController::class, 'index']);
Route::get('/sitemap/posts', [SitemapController::class, 'posts']);
Route::get('/sitemap/tags', [SitemapController::class, 'tags']);
Route::get('/sitemap/categories', [SitemapController::class, 'categories']);
Route::get('/sitemap/news-last-48-hours', [SitemapController::class, 'newsLast48Hours']);
