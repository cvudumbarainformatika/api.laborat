<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\PemesananController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Pemesanan\DialogrencanapemesananController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananController as PemesananPemesananController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/pemesananobat'
], function () {
    Route::get('/dialogrencanabeli', [DialogrencanapemesananController::class, 'dialogrencanabeli']);
    Route::get('/dialogrencanabeli_rinci', [DialogrencanapemesananController::class, 'dialogrencanabeli_rinci']);
    Route::post('/simpanpemesanan', [DialogrencanapemesananController::class, 'simpan']);
    Route::get('/listpemesanan', [PemesananPemesananController::class, 'listpemesanan']);
});
