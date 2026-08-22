<?php

use App\Http\Controllers\Api\V2\ReporterAuthController;
use App\Http\Controllers\Api\V2\ReporterNewsController;
use App\Http\Controllers\Api\V2\ReporterNoticeController;
use App\Http\Middleware\EnsureReporterActive;
use Illuminate\Support\Facades\Route;

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
