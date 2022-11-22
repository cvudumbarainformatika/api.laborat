<?php

use App\Http\Controllers\Api\Pegawai\User\LiburController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'libur'
], function () {
    Route::get('/index', [LiburController::class, 'index']);
    Route::post('/store', [LiburController::class, 'store']);
});
