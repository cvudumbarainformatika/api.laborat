<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\PembayaranController;
use Illuminate\Support\Facades\Route;

Route::group(
    [
        'middleware' => 'auth:api',
        'prefix' => 'transaksi/pembayaran'
    ],
    function () {
        Route::get('/cari-kontrak', [PembayaranController::class, 'cariKontrak']);
        Route::get('/ambil-kontrak', [PembayaranController::class, 'ambilKontrak']);
        Route::get('/ambil-penerimaan', [PembayaranController::class, 'ambilPenerimaan']);
        Route::get('/ambil-no-bayar', [PembayaranController::class, 'ambilNoBayar']);
    }
);
