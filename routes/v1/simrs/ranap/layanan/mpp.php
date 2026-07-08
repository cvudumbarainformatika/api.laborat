<?php

use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\MppController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/layanan/mpp/skrining'
], function () {
    Route::get('/list', [MppController::class, 'list']);
    Route::post('/simpandata', [MppController::class, 'simpandata']);
    Route::post('/hapusdata', [MppController::class, 'hapusdata']);
});
