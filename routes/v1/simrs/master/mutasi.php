<?php

use App\Http\Controllers\Api\Simrs\Master\AlasanMutasiController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/alasanmutasi',[AlasanMutasiController::class, 'index']);
});
