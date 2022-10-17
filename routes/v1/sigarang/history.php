<?php

use App\Http\Controllers\Api\v1\HistoryController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'history'
], function () {
    Route::get('/index', [HistoryController::class, 'index']);
    Route::get('/all', [HistoryController::class, 'allTransaction']);
    Route::post('/store', [HistoryController::class, 'store']);
    Route::post('/destroy', [HistoryController::class, 'destroy']);
});
