<?php

use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Hutang\HutangKonsinyasiController;
use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Hutang\HutangObatPesan;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/farmasi/hutang'
], function () {
    Route::get('/get-hutang-konsinyasi', [HutangKonsinyasiController::class, 'getHutangKonsinyasi']);
    Route::get('/reportObatPesananBytanggal', [HutangObatPesan::class, 'reportObatPesananBytanggal']);

});
