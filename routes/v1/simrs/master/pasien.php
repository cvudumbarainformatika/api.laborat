<?php

use App\Http\Controllers\Api\Simrs\Master\PasienController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/pasien',[PasienController::class, 'pasien']);
    Route::get('/pasienGetNoRM',[PasienController::class, 'index']);
    // Route::get('/pasienx',[PasienController::class, 'coba']);
});
