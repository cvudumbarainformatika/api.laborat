<?php

use App\Http\Controllers\Api\Pegawai\Absensi\KategoryController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'pegawai/absensi/kategori'
], function () {
    Route::get('/index', [KategoryController::class, 'index']);
    Route::get('/all', [KategoryController::class, 'all']);
    Route::post('/store', [KategoryController::class, 'store']);
    Route::post('/destroy', [KategoryController::class, 'destroy']);
});
