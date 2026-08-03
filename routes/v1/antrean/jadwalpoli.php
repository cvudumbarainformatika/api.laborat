<?php

use App\Http\Controllers\Api\Antrean\master\JadwalPoliController;
use Illuminate\Support\Facades\Route;



Route::group([
    // 'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'jadwalpoli'
], function () {
    Route::get('/data', [JadwalPoliController::class, 'index']);
    Route::get('/sync', [JadwalPoliController::class, 'sync']);
    Route::get('/rilis', [JadwalPoliController::class, 'rilis']);
});
