<?php

use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\PascaSedasiController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/ranap/layanan/pascasedasi'
], function () {
    Route::get('/get', [PascaSedasiController::class, 'get']);
    Route::get('/list', [PascaSedasiController::class, 'list']);
    Route::post('/store', [PascaSedasiController::class, 'store']);
    Route::post('/simpandata', [PascaSedasiController::class, 'simpandata']);
    Route::post('/destroy', [PascaSedasiController::class, 'destroy']);
    Route::post('/hapusdata', [PascaSedasiController::class, 'hapusdata']);
});
