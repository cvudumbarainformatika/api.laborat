<?php

use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Penerimaan\LaporanPerencanaanController;
use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Persediaan\PersediaanFiFoController;
use Illuminate\Support\Facades\Route;


// persediaan
Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/farmasi/persediaan'
], function () {
    Route::get('/get-persediaan', [PersediaanFiFoController::class, 'getPersediaan']);
    Route::get('/get-mutasi', [PersediaanFiFoController::class, 'getMutasi']);
    Route::get('/get-perencanaan', [LaporanPerencanaanController::class, 'perencanaanDanPenerimaan']);
});
