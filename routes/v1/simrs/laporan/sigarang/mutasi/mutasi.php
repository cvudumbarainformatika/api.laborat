<?php

use App\Http\Controllers\Api\Simrs\Laporan\Sigarang\LaporanMutasiGudangController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/sigarang'
], function () {
    Route::get('/lap-mutasi', [LaporanMutasiGudangController::class, 'lapMutasi']);
});
