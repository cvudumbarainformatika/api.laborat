<?php

use App\Http\Controllers\Api\Logistik\Sigarang\MinMaxStokController;
use App\Http\Controllers\Api\Logistik\Sigarang\MinMaxStokDepoController;
use App\Http\Controllers\Api\Logistik\Sigarang\MinMaxStokPenggunaController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'minmaxstok'
], function () {
    Route::get('/index', [MinMaxStokController::class, 'index']);
    Route::get('/minmaxstok', [MinMaxStokController::class, 'minmaxstok']);
    Route::post('/store', [MinMaxStokController::class, 'store']);
    Route::post('/destroy', [MinMaxStokController::class, 'destroy']);
});

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'minmaxdepostok'
], function () {
    Route::get('/index', [MinMaxStokDepoController::class, 'index']);
    Route::post('/store', [MinMaxStokDepoController::class, 'store']);
    Route::post('/destroy', [MinMaxStokDepoController::class, 'destroy']);
});

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'minmaxpenggunastok'
], function () {
    Route::get('/index', [MinMaxStokPenggunaController::class, 'index']);
    Route::post('/store', [MinMaxStokPenggunaController::class, 'store']);
    Route::post('/destroy', [MinMaxStokPenggunaController::class, 'destroy']);
});
