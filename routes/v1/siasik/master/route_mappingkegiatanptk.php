<?php

use App\Http\Controllers\Api\Siasik\Master\KegiatanBludController;
use App\Http\Controllers\Api\Siasik\Master\Mapping_KegiatanPtkController;
use App\Http\Controllers\Api\Siasik\Master\PTKController;
use Illuminate\Support\Facades\Route;
Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'master/siasik/mappingkegiatanptk'
], function () {
    Route::get('/index', [Mapping_KegiatanPtkController::class, 'index']);
    Route::post('/save', [Mapping_KegiatanPtkController::class, 'save']);
    Route::post('/delete', [Mapping_KegiatanPtkController::class, 'delete']);

});
