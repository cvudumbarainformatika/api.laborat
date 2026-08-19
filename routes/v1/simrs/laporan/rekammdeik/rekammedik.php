<?php

use App\Http\Controllers\Api\Simrs\Laporan\Ranap\LaporanPerkasusRanapController;
use App\Http\Controllers\Api\Simrs\Laporan\Rekammedik\LapcarakeluarpasienIgdController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/laporan/rekammdeik'
], function () {
    Route::get('/carakeluarpasienigd', [LapcarakeluarpasienIgdController::class, 'laporancarakeluarpasienigd']);
    Route::get('/perkasus', [LaporanPerkasusRanapController::class, 'index']);
});
