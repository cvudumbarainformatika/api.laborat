<?php

use App\Http\Controllers\Api\Mjkn\AmbilAntreanController;
use App\Http\Controllers\Api\Mjkn\SisaAntreanController;
use App\Http\Controllers\Api\Mjkn\StatuslayananController;
use Illuminate\Support\Facades\Route;



Route::group([
    // 'middleware' => 'auth:api',
    'middleware' => 'jkn.auth',
    'prefix' => 'mjkn'
], function () {
    Route::post('/status-antrean', [StatuslayananController::class, 'byLayanan']);  //mJkn (2)
    Route::post('/ambil-antrean', [AmbilAntreanController::class, 'byLayanan']);  //mJkn (3)
    Route::post('/sisa-antrean-pasien', [SisaAntreanController::class, 'byKodebooking']);  //mJkn (4)
});
