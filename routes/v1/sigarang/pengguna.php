<?php

use App\Http\Controllers\Api\v1\PenggunaController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'pengguna'
], function () {
    Route::get('/index', [PenggunaController::class, 'index']);
    Route::get('/pengguna', [PenggunaController::class, 'pengguna']);
    Route::post('/store', [PenggunaController::class, 'store']);
    Route::post('/destroy', [PenggunaController::class, 'destroy']);
});
