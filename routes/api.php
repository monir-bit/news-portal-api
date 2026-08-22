<?php

use App\Http\Controllers\ClubMemberController;
use App\Http\Controllers\CommentCardController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EventBannerController;
use App\Http\Controllers\GeoLocationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PageSeoController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\PopoverAddController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WebStoryController;
use App\Http\Controllers\WorldCupController;
use Illuminate\Support\Facades\Route;

// Route files are added per feature domain as each phase is implemented.

Route::get('/common', [CommonController::class, 'common']);
Route::get('/home', [HomeController::class, 'homeInitial']);

Route::get('/divisions', [GeoLocationController::class, 'divisions']);
Route::get('/districts/{divisionSlug}', [GeoLocationController::class, 'districts']);
Route::get('/upazilas/{districtSlug}', [GeoLocationController::class, 'upazilas']);

Route::get('/news-details/{slug}', [NewsController::class, 'newsDetails']);
Route::get('/news-by-category-home-batch', [NewsController::class, 'newsByCategoryHomeBatch']);
Route::get('/news-by-category-home/{slug}', [NewsController::class, 'newsByCategoryHome']);
Route::get('/news-by-category/{slug}', [NewsController::class, 'newsByCategory']);
Route::get('/news-by-print-category/{slug}', [NewsController::class, 'newsByPrintCategory']);
Route::get('/latest-news', [NewsController::class, 'latestNews']);
Route::get('/search', [NewsController::class, 'searchNews'])->middleware('throttle:search');
Route::get('/news-by-tags/{name}', [NewsController::class, 'newsByTags']);
Route::get('/news-by-author/{slug}', [NewsController::class, 'newsByAuthor']);

Route::get('/comment-card-summary', [CommentCardController::class, 'commentCardSummary']);
Route::get('/comment-card/{id}', [CommentCardController::class, 'details'])->whereNumber('id');

Route::get('/web-story-slider-data', [WebStoryController::class, 'sliderData']);
Route::get('/sports-web-story-slider-data', [WebStoryController::class, 'sportsWebHistory']);
Route::get('/web-story-details/{hash_key}', [WebStoryController::class, 'sliderDetails']);

Route::get('/polls', [PollController::class, 'index']);
Route::get('/polls/{id}', [PollController::class, 'show'])->whereNumber('id');
Route::get('/polls/by-page/{page}', [PollController::class, 'firstByPage']);
Route::post('/polls/{id}/vote', [PollController::class, 'vote'])->whereNumber('id')->middleware('throttle:votes');

Route::get('/question/{categorySlug}', [QuestionController::class, 'getQuestion']);
Route::get('/question/{categorySlug}/participation', [QuestionController::class, 'participation']);
Route::post('/question/{categorySlug}/answer', [QuestionController::class, 'submitAnswer'])->middleware('throttle:votes');

Route::post('/club/gold', [ClubMemberController::class, 'storeGold'])->middleware('throttle:public-forms');
Route::post('/club/kids', [ClubMemberController::class, 'storeKids'])->middleware('throttle:public-forms');
Route::post('/club/career', [ClubMemberController::class, 'storeCareer'])->middleware('throttle:public-forms');

Route::get('/pages', [PageController::class, 'index']);
Route::get('/page/{name}', [PageController::class, 'show']);
Route::get('/page-seo/{name}', [PageSeoController::class, 'get']);

Route::get('/popover-add/active', [PopoverAddController::class, 'active']);
Route::get('/event-banner/{name}', [EventBannerController::class, 'show']);
Route::get('/employees', [EmployeeController::class, 'index']);

Route::get('/sitemap', [SitemapController::class, 'index']);
Route::get('/sitemap/posts', [SitemapController::class, 'posts']);
Route::get('/sitemap/tags', [SitemapController::class, 'tags']);
Route::get('/sitemap/categories', [SitemapController::class, 'categories']);
Route::get('/sitemap/news-48h', [SitemapController::class, 'newsLast48Hours']);

Route::get('/election/results', [ElectionController::class, 'results']);
Route::get('/election/summary', [ElectionController::class, 'summary']);

Route::get('/world-cup-match-details/{id}', [WorldCupController::class, 'matchDetails'])->whereNumber('id');
Route::get('/world-cup-all-matches', [WorldCupController::class, 'allMatches']);
Route::get('/world-cup-today-match', [WorldCupController::class, 'todayMatch']);

// World Cup trivia questions + quiz sets and the /news-by-category-world-cup landing
// page are not yet implemented — pending Phase 3 completion.
