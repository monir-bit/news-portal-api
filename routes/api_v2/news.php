<?php

use App\Http\Controllers\Api\V2\HomeController;
use App\Http\Controllers\Api\V2\NewsController;
use Illuminate\Support\Facades\Route;

// --- News & Home ---
Route::get('/home', [HomeController::class, 'initial']);

Route::get('/news-details/{slug}', [NewsController::class, 'details']);
Route::get('/news-by-category-home-batch', [NewsController::class, 'byCategoryHomeBatch']);
Route::get('/news-by-category-home/{slug}', [NewsController::class, 'byCategoryHome']);
Route::get('/news-by-category/{slug}', [NewsController::class, 'byCategory']);
Route::get('/news-by-category-sports', [NewsController::class, 'bySportsCategory']);
Route::get('/news-by-category-world-cup', [NewsController::class, 'byWorldCupCategory']);
Route::get('/news-by-category-print', [NewsController::class, 'byPrintHome']);
Route::get('/news-by-print-category/{slug}', [NewsController::class, 'byPrintCategory']);
Route::get('/latest-news', [NewsController::class, 'latest']);
Route::get('/search', [NewsController::class, 'search'])->middleware('throttle:search');
Route::get('/news-by-tags/{name}', [NewsController::class, 'byTag']);
Route::get('/news-by-author/{slug}', [NewsController::class, 'byAuthor']);
Route::get('/related-news/{news}', [NewsController::class, 'related']);
