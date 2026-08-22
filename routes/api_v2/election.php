<?php

use App\Http\Controllers\Api\V2\ElectionController;
use Illuminate\Support\Facades\Route;

Route::get('/election/results', [ElectionController::class, 'results']);
Route::get('/election/summary', [ElectionController::class, 'summary']);
