<?php

use App\Http\Controllers\Api\Siasik\Master\PTKController;
use Illuminate\Support\Facades\Route;
Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'master/siasik/ptk'
], function () {
    Route::get('/getpegawai', [PTKController::class, 'getPegawai']);
    Route::get('/index', [PTKController::class, 'index']);
    Route::post('/save', [PTKController::class, 'save']);
    Route::post('/delete', [PTKController::class, 'delete']);

});
