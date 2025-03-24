<?php

use App\Http\Controllers\Api\Arsip\Master\MkelasifikasiController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'arsip/master'
],function () {
    Route::post('/simpankelasifikasi', [MkelasifikasiController::class, 'simpan']);
    Route::post('/deletekelasifikasi', [MkelasifikasiController::class, 'hapuskelasifikasi']);
    Route::get('/getmasterarsip', [MkelasifikasiController::class, 'listmkelasifikasi']);
});



