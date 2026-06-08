<?php

use App\Http\Controllers\Api\Simrs\Laporan\Arsip\daftarArsipController;
use App\Http\Controllers\Api\Simrs\Laporan\Arsip\daftarberkasController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/arsip/daftarberkas'
], function () {
    Route::get('/get-data', [daftarberkasController::class, 'getData']);
    Route::get('/get-datax', [daftarArsipController::class, 'getData']);
});
