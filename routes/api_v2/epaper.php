<?php

use App\Http\Controllers\Api\V2\EpaperQuestionController;
use App\Http\Controllers\Api\V2\EpaperReaderController;
use Illuminate\Support\Facades\Route;

// --- Epaper Question (quiz) ---
Route::get('/epaper-question/grid', [EpaperQuestionController::class, 'grid']);
Route::get('/epaper-question/pages/{page}/questions', [EpaperQuestionController::class, 'pageQuestions'])
    ->where('page', '^([1-9]|1[0-6])$');
Route::get('/epaper-question/questions/{question}', [EpaperQuestionController::class, 'show'])
    ->whereNumber('question');
Route::get('/epaper-question/participation', [EpaperQuestionController::class, 'participation']);
Route::post('/epaper-question/answer', [EpaperQuestionController::class, 'submitAnswer'])->middleware('throttle:votes');

// --- Epaper Reader ---
Route::get('/epaper/publications', [EpaperReaderController::class, 'publications']);
Route::get('/epaper/{slug}/{date}/download-crops', [EpaperReaderController::class, 'downloadCrops']);
Route::get('/epaper/{slug}/{date}/download-page', [EpaperReaderController::class, 'downloadPage']);
Route::get('/epaper/{slug}/{date}', [EpaperReaderController::class, 'show']);
