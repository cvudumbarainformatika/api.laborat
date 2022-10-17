<?php

use App\Http\Controllers\Api\v1\RuangController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'ruang'
], function () {
    Route::get('/index', [RuangController::class, 'index']);
    Route::get('/ruang', [RuangController::class, 'ruang']);
    Route::post('/store', [RuangController::class, 'store']);
    Route::post('/destroy', [RuangController::class, 'destroy']);
});
