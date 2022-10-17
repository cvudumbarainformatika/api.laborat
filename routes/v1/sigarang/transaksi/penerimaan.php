<?php

use App\Http\Controllers\Api\v1\Transaksi\PenerimaanController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi/penerimaan'
], function () {
    // Route::get('/index', [SupplierController::class, 'index']);
    Route::get('/cari-pemesanan', [PenerimaanController::class, 'cariPemesanan']);
    Route::get('/penerimaan', [PenerimaanController::class, 'penerimaan']);
    Route::post('/simpan-penerimaan', [PenerimaanController::class, 'simpanPenerimaan']);
    Route::post('/destroy', [PenerimaanController::class, 'destroy']);
});
