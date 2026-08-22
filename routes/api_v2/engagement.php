<?php

use App\Http\Controllers\Api\V2\CommentCardController;
use App\Http\Controllers\Api\V2\PollController;
use App\Http\Controllers\Api\V2\QuestionController;
use Illuminate\Support\Facades\Route;

// --- Category questions (not world-cup/epaper questions) ---
Route::get('/question/{categorySlug}/participation', [QuestionController::class, 'participation']);
Route::post('/question/{categorySlug}/answer', [QuestionController::class, 'submitAnswer'])->middleware('throttle:votes');
Route::get('/question/{categorySlug}', [QuestionController::class, 'getQuestion']);

// --- Polls ---
Route::get('/polls/by-page/{page}', [PollController::class, 'firstByPage']);
Route::post('/polls/{id}/vote', [PollController::class, 'vote'])->whereNumber('id')->middleware('throttle:votes');
Route::get('/polls/{id}', [PollController::class, 'show'])->whereNumber('id');
Route::get('/polls', [PollController::class, 'index']);

// --- Comment cards ---
Route::get('/comment-card-summary', [CommentCardController::class, 'commentCardSummary']);
Route::get('/comment-card/{id}', [CommentCardController::class, 'details']);
