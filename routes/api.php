<?php

use App\Http\Controllers\Api\ClubMemberApiController;
use App\Http\Controllers\Api\CommentCardController;
use App\Http\Controllers\Api\CommonController;
use App\Http\Controllers\Api\ElectionController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EpaperQuestionApiController;
use App\Http\Controllers\Api\EpaperReaderController;
use App\Http\Controllers\Api\EventBannerApiController;
use App\Http\Controllers\Api\GeoLocationController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PageSeoController;
use App\Http\Controllers\Api\PollController;
use App\Http\Controllers\Api\PopoverAddApiController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\ReporterAuthController;
use App\Http\Controllers\Api\ReporterNewsController;
use App\Http\Controllers\Api\ReporterNoticeController;
use App\Http\Controllers\Api\WebStoryController;
use App\Http\Controllers\Api\WorldCupController;
use App\Http\Controllers\Api\WorldCupQuestionController;
use App\Http\Controllers\Api\WorldCupQuizSetController;
use App\Http\Middleware\EnsureReporterActive;
use Illuminate\Support\Facades\Route;

Route::post('/club/gold', [ClubMemberApiController::class, 'storeGold'])->middleware('throttle:public-forms');
Route::post('/club/kids', [ClubMemberApiController::class, 'storeKids'])->middleware('throttle:public-forms');
Route::post('/club/career', [ClubMemberApiController::class, 'storeCareer'])->middleware('throttle:public-forms');

Route::get('/common', [CommonController::class, 'common']);
Route::get('/popover-add/active', [PopoverAddApiController::class, 'active']);
Route::get('/event-banner/{name}', [EventBannerApiController::class, 'show']);
Route::get('/divisions', [GeoLocationController::class, 'divisions']);
Route::get('/districts/{divisionSlug}', [GeoLocationController::class, 'districts']);
Route::get('/upazilas/{districtSlug}', [GeoLocationController::class, 'upazilas']);

Route::prefix('reporter')->name('reporter.')->group(function () {
    Route::post('/login', [ReporterAuthController::class, 'login'])->middleware('throttle:reporter-login')->name('login');
    Route::middleware(['auth:sanctum', EnsureReporterActive::class])->group(function () {
        Route::get('/profile', [ReporterAuthController::class, 'profile'])->name('profile');
        Route::put('/profile', [ReporterAuthController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/image', [ReporterAuthController::class, 'uploadProfileImage'])->name('profile.image');
        Route::post('/change-password', [ReporterAuthController::class, 'changePassword'])->middleware('throttle:sensitive')->name('change-password');
        Route::post('/logout', [ReporterAuthController::class, 'logout'])->name('logout');
        Route::get('/news/submit-options', [ReporterNewsController::class, 'submitOptions'])->name('news.submit-options');
        Route::get('/news', [ReporterNewsController::class, 'index'])->name('news.index');
        Route::post('/news', [ReporterNewsController::class, 'store'])->name('news.store');
        Route::post('/news/media', [ReporterNewsController::class, 'uploadMedia'])->name('news.media.upload');
        Route::get('/news/{reporter_news_id}/updates', [ReporterNewsController::class, 'indexUpdates'])->name('news.updates.index');
        Route::post('/news/{reporter_news_id}/updates', [ReporterNewsController::class, 'storeUpdate'])->name('news.updates.store');
        Route::get('/notices', [ReporterNoticeController::class, 'index'])->name('notices.index');
        Route::get('/notices/{id}', [ReporterNoticeController::class, 'show'])->name('notices.show');
        Route::post('/notices/{id}/opinion', [ReporterNoticeController::class, 'storeOpinion'])->name('notices.opinion.store');
    });
});

Route::get('/home', [HomeController::class, 'homeInitial']);
Route::get('/question/{categorySlug}/participation', [QuestionController::class, 'participation']);
Route::post('/question/{categorySlug}/answer', [QuestionController::class, 'submitAnswer'])->middleware('throttle:votes');
Route::get('/question/{categorySlug}', [QuestionController::class, 'getQuestion']);

Route::get('/epaper-question/grid', [EpaperQuestionApiController::class, 'grid']);
Route::get('/epaper-question/pages/{page}/questions', [EpaperQuestionApiController::class, 'pageQuestions'])
    ->where('page', '^([1-9]|1[0-6])$');
Route::get('/epaper-question/questions/{question}', [EpaperQuestionApiController::class, 'show'])
    ->whereNumber('question');
Route::get('/epaper-question/participation', [EpaperQuestionApiController::class, 'participation']);
Route::post('/epaper-question/answer', [EpaperQuestionApiController::class, 'submitAnswer'])->middleware('throttle:votes');
Route::get('/news-details/{slug}', [NewsController::class, 'newsDetails']);
Route::get('/news-by-category-home-batch', [NewsController::class, 'newsByCategoryHomeBatch']);
Route::get('/news-by-category-home/{slug}', [NewsController::class, 'newsByCategoryHome']);
Route::get('/news-by-category/{slug}', [NewsController::class, 'newsByCategory']);
Route::get('/news-by-category-sports', [NewsController::class, 'newsByCategorySports']);

Route::get('/news-by-category-world-cup', [NewsController::class, 'newsByCategoryWorldCup']);
Route::get('/world-cup-match-details/{id}', [WorldCupController::class, 'matchDetails']);
Route::get('/world-cup-all-matches', [WorldCupController::class, 'allMatches']);

Route::get('/news-by-category-print', [NewsController::class, 'newsByCategoryPrint']);
Route::get('/news-by-print-category/{slug}', [NewsController::class, 'newsByPrintCategory']);
Route::get('/latest-news', [NewsController::class, 'latestNews']);
Route::get('/search', [NewsController::class, 'searchNews'])->middleware('throttle:search');
Route::get('/news-by-tags/{name}', [NewsController::class, 'newsByTags']);
Route::get('/news-by-author/{slug}', [NewsController::class, 'newsByAuthor']);
Route::get('/election/results', [ElectionController::class, 'results']);
Route::get('/election/summary', [ElectionController::class, 'summary']);

Route::get('/polls/by-page/{page}', [PollController::class, 'firstByPage']);
Route::post('/polls/{id}/vote', [PollController::class, 'vote'])->whereNumber('id')->middleware('throttle:votes');
Route::get('/polls/{id}', [PollController::class, 'show'])->whereNumber('id');
Route::get('/polls', [PollController::class, 'index']);

Route::get('/world-cup-quiz-sets', [WorldCupQuizSetController::class, 'index']);
Route::get('/world-cup-quiz-sets/{slug}', [WorldCupQuizSetController::class, 'show']);
Route::get('/world-cup-quiz-sets/{slug}/progress', [WorldCupQuizSetController::class, 'progress']);
Route::post('/world-cup-quiz-sets/{slug}/start', [WorldCupQuizSetController::class, 'start']);
Route::post('/world-cup-quiz-sets/{slug}/answer', [WorldCupQuizSetController::class, 'submitAnswer'])->middleware('throttle:votes');
Route::get('/world-cup-questions', [WorldCupQuestionController::class, 'index']);
Route::get('/world-cup-questions/progress', [WorldCupQuestionController::class, 'progress']);
Route::post('/world-cup-questions/{id}/submit', [WorldCupQuestionController::class, 'submit'])->whereNumber('id')->middleware('throttle:votes');
Route::get('/world-cup-today-match', [WorldCupController::class, 'todayMatch']);

Route::get('/comment-card-summary', [CommentCardController::class, 'commentCardSummary']);
Route::get('/web-story-slider-data', [WebStoryController::class, 'sliderData']);
Route::get('/sports-web-story-slider-data', [WebStoryController::class, 'sportsWebHistory']);
Route::get('/web-story-details/{hash_key}', [WebStoryController::class, 'sliderDetails']);
Route::get('/comment-card/{id}', [CommentCardController::class, 'details']);
Route::get('/employees', [EmployeeController::class, 'index']);

Route::get('/epaper/publications', [EpaperReaderController::class, 'publications']);
Route::get('/epaper/{slug}/{date}/download-crops', [EpaperReaderController::class, 'downloadCrops']);
Route::get('/epaper/{slug}/{date}/download-page', [EpaperReaderController::class, 'downloadPage']);
Route::get('/epaper/{slug}/{date}', [EpaperReaderController::class, 'show']);

// Static pages (terms, about, contact, privacy)
Route::get('/page/{name}', [PageController::class, 'show']);
Route::get('/pages', [PageController::class, 'index']);
Route::get('/page-seo/{name}', [PageSeoController::class, 'get']);

Route::get('/test', function () {
    return 'hello world';
});
