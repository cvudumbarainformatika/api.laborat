<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang\KonsinyasiController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/farmasinew/bast-konsi'
], function () {
    Route::get('/penyedia', [KonsinyasiController::class, 'getPenyedia']);
    // Route::get('/pemesanan', [BastController::class, 'pemesanan']);
    // Route::get('/penerimaan', [BastController::class, 'penerimaan']);

    Route::post('/simpan-list', [KonsinyasiController::class, 'simpanListKonsinyasi']);

    // Route::get('/list-bast', [BastController::class, 'listBast']);
});
