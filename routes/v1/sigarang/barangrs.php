<?php

use App\Http\Controllers\Api\Logistik\Sigarang\BarangRSController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'barangrs'
], function () {
    Route::get('/index', [BarangRSController::class, 'index']);
    Route::get('/barangrs', [BarangRSController::class, 'barangrs']);
    Route::post('/store', [BarangRSController::class, 'store']);
    Route::post('/destroy', [BarangRSController::class, 'destroy']);
});
