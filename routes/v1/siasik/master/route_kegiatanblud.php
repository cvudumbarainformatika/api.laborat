<?php

use App\Http\Controllers\Api\Siasik\Master\KegiatanBludController;
use App\Http\Controllers\Api\Siasik\Master\PTKController;
use Illuminate\Support\Facades\Route;
Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'master/siasik/kegiatanblud'
], function () {
    Route::get('/getbidang', [KegiatanBludController::class, 'getBidang']);
    Route::get('/index', [KegiatanBludController::class, 'index']);
    Route::post('/save', [KegiatanBludController::class, 'save']);
    Route::post('/delete', [KegiatanBludController::class, 'delete']);

});
