<?php

use App\Http\Controllers\Api\Simrs\Master\SistemBayarController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/sistembayar',[SistemBayarController::class, 'index']);
});
