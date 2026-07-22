<?php

use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\DaftarTilikController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/ranap/layanan/daftartilik'
], function () {
    Route::get('/list', [DaftarTilikController::class, 'list']);
    Route::post('/simpandata', [DaftarTilikController::class, 'simpandata']);
    Route::post('/hapusdata', [DaftarTilikController::class, 'hapusdata']);
});
