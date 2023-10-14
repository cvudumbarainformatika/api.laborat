<?php

use App\Http\Controllers\Api\Simrs\Laporan\Sigarang\LaporanRuanganController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/sigarang'
], function () {
    Route::get('ruangan/barang', [LaporanRuanganController::class, 'getBarang']);
    Route::get('pengeluaran-depo', [LaporanRuanganController::class, 'lapPengeluaranDepo']);
});
