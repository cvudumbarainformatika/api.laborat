<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\PenerimaanController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi/penerimaan'
], function () {
    // Route::get('/index', [SupplierController::class, 'index']);
    Route::get('/cari-pemesanan', [PenerimaanController::class, 'cariPemesanan']);
    Route::get('/jumlah-penerimaan', [PenerimaanController::class, 'jumlahPenerimaan']);
    Route::get('/penerimaan', [PenerimaanController::class, 'penerimaan']);
    Route::get('/surat-belum-lengkap', [PenerimaanController::class, 'suratBelumLengkap']);
    Route::post('/simpan-penerimaan', [PenerimaanController::class, 'simpanPenerimaan']);
    Route::post('/lengkapi-surat', [PenerimaanController::class, 'lengkapiSurat']);
    Route::post('/destroy', [PenerimaanController::class, 'destroy']);
});
