<?php

use App\Http\Controllers\Api\Siasik\Laporan\LRAController;
use Illuminate\Support\Facades\Route;


Route::group([
    // 'middleware' => 'auth:api',
    'prefix' => 'laporan/lra'
], function () {
    Route::get('/lra', [LRAController::class, 'lra']);
    Route::get('/coba', [LRAController::class, 'coba']);

});


