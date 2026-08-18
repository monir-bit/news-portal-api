<?php

use App\Http\Controllers\Api\ReporterAuthController;
use App\Http\Controllers\Api\WorldCupController;
use Illuminate\Support\Facades\Route;

Route::post('/club/gold', [\App\Http\Controllers\Api\ClubMemberApiController::class, 'storeGold'])->middleware('throttle:public-forms');
Route::post('/club/kids', [\App\Http\Controllers\Api\ClubMemberApiController::class, 'storeKids'])->middleware('throttle:public-forms');
Route::post('/club/career', [\App\Http\Controllers\Api\ClubMemberApiController::class, 'storeCareer'])->middleware('throttle:public-forms');

Route::get('/common', [\App\Http\Controllers\Api\CommonController::class, 'common']);
Route::get('/popover-add/active', [\App\Http\Controllers\Api\PopoverAddApiController::class, 'active']);
Route::get('/event-banner/{name}', [\App\Http\Controllers\Api\EventBannerApiController::class, 'show']);
Route::get('/divisions', [\App\Http\Controllers\Api\GeoLocationController::class, 'divisions']);
Route::get('/districts/{divisionSlug}', [\App\Http\Controllers\Api\GeoLocationController::class, 'districts']);
Route::get('/upazilas/{districtSlug}', [\App\Http\Controllers\Api\GeoLocationController::class, 'upazilas']);

Route::prefix('reporter')->name('reporter.')->group(function () {
    Route::post('/login', [ReporterAuthController::class, 'login'])->middleware('throttle:reporter-login')->name('login');
    Route::middleware(['auth:sanctum', \App\Http\Middleware\EnsureReporterActive::class])->group(function () {
        Route::get('/profile', [ReporterAuthController::class, 'profile'])->name('profile');
        Route::put('/profile', [ReporterAuthController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/image', [ReporterAuthController::class, 'uploadProfileImage'])->name('profile.image');
        Route::post('/change-password', [ReporterAuthController::class, 'changePassword'])->middleware('throttle:sensitive')->name('change-password');
        Route::post('/logout', [ReporterAuthController::class, 'logout'])->name('logout');
        Route::get('/news/submit-options', [\App\Http\Controllers\Api\ReporterNewsController::class, 'submitOptions'])->name('news.submit-options');
        Route::get('/news', [\App\Http\Controllers\Api\ReporterNewsController::class, 'index'])->name('news.index');
        Route::post('/news', [\App\Http\Controllers\Api\ReporterNewsController::class, 'store'])->name('news.store');
        Route::post('/news/media', [\App\Http\Controllers\Api\ReporterNewsController::class, 'uploadMedia'])->name('news.media.upload');
        Route::get('/news/{reporter_news_id}/updates', [\App\Http\Controllers\Api\ReporterNewsController::class, 'indexUpdates'])->name('news.updates.index');
        Route::post('/news/{reporter_news_id}/updates', [\App\Http\Controllers\Api\ReporterNewsController::class, 'storeUpdate'])->name('news.updates.store');
        Route::get('/notices', [\App\Http\Controllers\Api\ReporterNoticeController::class, 'index'])->name('notices.index');
        Route::get('/notices/{id}', [\App\Http\Controllers\Api\ReporterNoticeController::class, 'show'])->name('notices.show');
        Route::post('/notices/{id}/opinion', [\App\Http\Controllers\Api\ReporterNoticeController::class, 'storeOpinion'])->name('notices.opinion.store');
    });
});

Route::get('/home', [\App\Http\Controllers\Api\HomeController::class, 'homeInitial']);
Route::get('/question/{categorySlug}/participation', [\App\Http\Controllers\Api\QuestionController::class, 'participation']);
Route::post('/question/{categorySlug}/answer', [\App\Http\Controllers\Api\QuestionController::class, 'submitAnswer'])->middleware('throttle:votes');
Route::get('/question/{categorySlug}', [\App\Http\Controllers\Api\QuestionController::class, 'getQuestion']);

Route::get('/epaper-question/grid', [\App\Http\Controllers\Api\EpaperQuestionApiController::class, 'grid']);
Route::get('/epaper-question/pages/{page}/questions', [\App\Http\Controllers\Api\EpaperQuestionApiController::class, 'pageQuestions'])
    ->where('page', '^([1-9]|1[0-6])$');
Route::get('/epaper-question/questions/{question}', [\App\Http\Controllers\Api\EpaperQuestionApiController::class, 'show'])
    ->whereNumber('question');
Route::get('/epaper-question/participation', [\App\Http\Controllers\Api\EpaperQuestionApiController::class, 'participation']);
Route::post('/epaper-question/answer', [\App\Http\Controllers\Api\EpaperQuestionApiController::class, 'submitAnswer'])->middleware('throttle:votes');
Route::get('/news-details/{slug}', [\App\Http\Controllers\Api\NewsController::class, 'newsDetails']);
Route::get('/news-by-category-home-batch', [\App\Http\Controllers\Api\NewsController::class, 'newsByCategoryHomeBatch']);
Route::get('/news-by-category-home/{slug}', [\App\Http\Controllers\Api\NewsController::class, 'newsByCategoryHome']);
Route::get('/news-by-category/{slug}', [\App\Http\Controllers\Api\NewsController::class, 'newsByCategory']);
Route::get('/news-by-category-sports', [\App\Http\Controllers\Api\NewsController::class, 'newsByCategorySports']);

Route::get('/news-by-category-world-cup', [\App\Http\Controllers\Api\NewsController::class, 'newsByCategoryWorldCup']);
Route::get('/world-cup-match-details/{id}', [\App\Http\Controllers\Api\WorldCupController::class, 'matchDetails']);
Route::get('/world-cup-all-matches', [\App\Http\Controllers\Api\WorldCupController::class, 'allMatches']);


Route::get('/news-by-category-print', [\App\Http\Controllers\Api\NewsController::class, 'newsByCategoryPrint']);
Route::get('/news-by-print-category/{slug}', [\App\Http\Controllers\Api\NewsController::class, 'newsByPrintCategory']);
Route::get('/latest-news', [\App\Http\Controllers\Api\NewsController::class, 'latestNews']);
Route::get('/search', [\App\Http\Controllers\Api\NewsController::class, 'searchNews'])->middleware('throttle:search');
Route::get('/news-by-tags/{name}', [\App\Http\Controllers\Api\NewsController::class, 'newsByTags']);
Route::get('/news-by-author/{slug}', [\App\Http\Controllers\Api\NewsController::class, 'newsByAuthor']);
Route::get('/election/results', [\App\Http\Controllers\Api\ElectionController::class, 'results']);
Route::get('/election/summary', [\App\Http\Controllers\Api\ElectionController::class, 'summary']);

Route::get('/polls/by-page/{page}', [\App\Http\Controllers\Api\PollController::class, 'firstByPage']);
Route::post('/polls/{id}/vote', [\App\Http\Controllers\Api\PollController::class, 'vote'])->whereNumber('id')->middleware('throttle:votes');
Route::get('/polls/{id}', [\App\Http\Controllers\Api\PollController::class, 'show'])->whereNumber('id');
Route::get('/polls', [\App\Http\Controllers\Api\PollController::class, 'index']);

Route::get('/world-cup-quiz-sets', [\App\Http\Controllers\Api\WorldCupQuizSetController::class, 'index']);
Route::get('/world-cup-quiz-sets/{slug}', [\App\Http\Controllers\Api\WorldCupQuizSetController::class, 'show']);
Route::get('/world-cup-quiz-sets/{slug}/progress', [\App\Http\Controllers\Api\WorldCupQuizSetController::class, 'progress']);
Route::post('/world-cup-quiz-sets/{slug}/start', [\App\Http\Controllers\Api\WorldCupQuizSetController::class, 'start']);
Route::post('/world-cup-quiz-sets/{slug}/answer', [\App\Http\Controllers\Api\WorldCupQuizSetController::class, 'submitAnswer'])->middleware('throttle:votes');
Route::get('/world-cup-questions', [\App\Http\Controllers\Api\WorldCupQuestionController::class, 'index']);
Route::get('/world-cup-questions/progress', [\App\Http\Controllers\Api\WorldCupQuestionController::class, 'progress']);
Route::post('/world-cup-questions/{id}/submit', [\App\Http\Controllers\Api\WorldCupQuestionController::class, 'submit'])->whereNumber('id')->middleware('throttle:votes');
Route::get('/world-cup-today-match', [WorldCupController::class, 'todayMatch']);


Route::get('/comment-card-summary', [\App\Http\Controllers\Api\CommentCardController::class, 'commentCardSummary']);
Route::get('/web-story-slider-data', [\App\Http\Controllers\Api\WebStoryController::class, 'sliderData']);
Route::get('/sports-web-story-slider-data', [\App\Http\Controllers\Api\WebStoryController::class, 'sportsWebHistory']);
Route::get('/web-story-details/{hash_key}', [\App\Http\Controllers\Api\WebStoryController::class, 'sliderDetails']);
Route::get('/comment-card/{id}', [\App\Http\Controllers\Api\CommentCardController::class, 'details']);
Route::get('/employees', [\App\Http\Controllers\Api\EmployeeController::class, 'index']);

Route::get('/epaper/publications', [\App\Http\Controllers\Api\EpaperReaderController::class, 'publications']);
Route::get('/epaper/{slug}/{date}/download-crops', [\App\Http\Controllers\Api\EpaperReaderController::class, 'downloadCrops']);
Route::get('/epaper/{slug}/{date}/download-page', [\App\Http\Controllers\Api\EpaperReaderController::class, 'downloadPage']);
Route::get('/epaper/{slug}/{date}', [\App\Http\Controllers\Api\EpaperReaderController::class, 'show']);

// Static pages (terms, about, contact, privacy)
Route::get('/page/{name}', [\App\Http\Controllers\Api\PageController::class, 'show']);
Route::get('/pages', [\App\Http\Controllers\Api\PageController::class, 'index']);
Route::get('/page-seo/{name}', [\App\Http\Controllers\Api\PageSeoController::class, 'get']);

// Sitemap API (for news-portal-client sitemap generation)
Route::prefix('sitemap')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\SitemapController::class, 'index']);
    Route::get('/posts', [\App\Http\Controllers\Api\SitemapController::class, 'posts']);
    Route::get('/categories', [\App\Http\Controllers\Api\SitemapController::class, 'categories']);
    Route::get('/tags', [\App\Http\Controllers\Api\SitemapController::class, 'tags']);
    Route::get('/news-last-48h', [\App\Http\Controllers\Api\SitemapController::class, 'newsLast48Hours']);
});

Route::get('/sitemap', [\App\Http\Controllers\SitemapController::class, 'index']);

Route::get('/test', function () {
    return 'hello world';
});
