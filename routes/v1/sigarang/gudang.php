<?php

use App\Http\Controllers\Api\v1\GudangController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'gudang'
], function () {
    Route::get('/index', [GudangController::class, 'index']);
    Route::post('/store', [GudangController::class, 'store']);
    Route::post('/destroy', [GudangController::class, 'destroy']);
});
