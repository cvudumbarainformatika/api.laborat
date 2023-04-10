<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\BastController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'transaksi/bast'
], function () {
    Route::get('/perusahaan', [BastController::class, 'cariPerusahaan']);
    Route::get('/nomor-pemesanan', [BastController::class, 'cariPemesanan']);
    Route::get('/pemesanan', [BastController::class, 'ambilPemesanan']);
});
