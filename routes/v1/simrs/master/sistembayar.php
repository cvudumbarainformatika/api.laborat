<?php

use App\Http\Controllers\Api\Simrs\master\SistemBayar_ar;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/index',[SistemBayar_ar::class, 'index']);
});
