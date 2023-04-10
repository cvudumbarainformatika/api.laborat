<?php

use App\Http\Controllers\Api\Pegawai\User\DispenController;
use App\Http\Controllers\Api\Pegawai\User\LiburController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'libur'
], function () {
    Route::get('/index', [LiburController::class, 'index']);
    Route::get('/month', [LiburController::class, 'month']);
    Route::post('/store', [LiburController::class, 'store']);
});
Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'dispen'
], function () {
    Route::get('/pegawai', [DispenController::class, 'index']);
});
Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'jadwal'
], function () {
    Route::get('/lebaran', [LiburController::class, 'lebaran']);
    Route::post('/ramadhan', [LiburController::class, 'ramadhan']);
});

Route::group([
    // 'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'absen'
], function () {
    Route::get('/alpha', [LiburController::class, 'tulisTidakMasuk']);
    // Route::post('/ramadhan', [LiburController::class, 'ramadhan']);
});
