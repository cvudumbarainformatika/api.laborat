<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Barang108Controller;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'barang108'
], function () {
    Route::get('/index', [Barang108Controller::class, 'index']);
    Route::get('/maping', [Barang108Controller::class, 'maping']);
    Route::get('/barang108', [Barang108Controller::class, 'barang108']);
    Route::get('/maping-50', [Barang108Controller::class, 'maping108to50']);
    Route::post('/store', [Barang108Controller::class, 'store']);
    Route::post('/destroy', [Barang108Controller::class, 'destroy']);
    Route::get('/indexall', [Barang108Controller::class, 'indexall']);
});
