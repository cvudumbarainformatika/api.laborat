<?php

use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\PraSedasiController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/ranap/layanan/prasedasi'
], function () {
    Route::get('/list', [PraSedasiController::class, 'list']);
    Route::post('/simpandata', [PraSedasiController::class, 'simpandata']);
    Route::post('/hapusdata', [PraSedasiController::class, 'hapusdata']);
});
