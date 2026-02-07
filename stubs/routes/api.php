<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BuzzController;

Route::get('/health', function () {
    return response()->json(['ok' => true]);
});

Route::get('/buzz/state', [BuzzController::class, 'state']);
Route::post('/buzz/press', [BuzzController::class, 'press']);
Route::post('/buzz/reset', [BuzzController::class, 'reset']);
Route::post('/buzz/hard-reset', [BuzzController::class, 'hardReset']);
