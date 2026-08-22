<?php

use App\Http\Controllers\Api\V2\WorldCupController;
use App\Http\Controllers\Api\V2\WorldCupQuestionController;
use App\Http\Controllers\Api\V2\WorldCupQuizSetController;
use Illuminate\Support\Facades\Route;

// --- World Cup: matches ---
Route::get('/world-cup-match-details/{id}', [WorldCupController::class, 'matchDetails']);
Route::get('/world-cup-all-matches', [WorldCupController::class, 'allMatches']);
Route::get('/world-cup-today-match', [WorldCupController::class, 'todayMatch']);

// --- World Cup: quiz sets ---
Route::get('/world-cup-quiz-sets', [WorldCupQuizSetController::class, 'index']);
Route::get('/world-cup-quiz-sets/{slug}', [WorldCupQuizSetController::class, 'show']);
Route::get('/world-cup-quiz-sets/{slug}/progress', [WorldCupQuizSetController::class, 'progress']);
Route::post('/world-cup-quiz-sets/{slug}/start', [WorldCupQuizSetController::class, 'start']);
Route::post('/world-cup-quiz-sets/{slug}/answer', [WorldCupQuizSetController::class, 'submitAnswer'])->middleware('throttle:votes');

// --- World Cup: questions ---
Route::get('/world-cup-questions', [WorldCupQuestionController::class, 'index']);
Route::get('/world-cup-questions/progress', [WorldCupQuestionController::class, 'progress']);
Route::post('/world-cup-questions/{id}/submit', [WorldCupQuestionController::class, 'submit'])->whereNumber('id')->middleware('throttle:votes');
