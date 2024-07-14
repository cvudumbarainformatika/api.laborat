<?php

use App\Http\Controllers\Api\Siasik\Laporan\LRAController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'laporan/lra'
], function () {
    Route::get('/bidang', [LRAController::class, 'bidang']);
    Route::get('/laplra', [LRAController::class, 'laplra']);
    Route::get('/coba', [LRAController::class, 'coba']);
    Route::get('/pendapatan', [LRAController::class, 'pendapatan']);

});


