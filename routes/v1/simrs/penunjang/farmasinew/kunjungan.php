<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\KunjunganController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/farmasinew/kunjungan'
], function () {
    // pelayanan informasi Obat (PIO)
    Route::post('/simpan-pelayanan-informasi-obat', [KunjunganController::class, 'simPelIOnfOb']);

    // pelayanan edukasi farmasi
    Route::post('/simpan-edukasi-farmasi', [KunjunganController::class, 'simpanEdukasiFarmasi']);
    Route::get('/get-edukasi-farmasi', [KunjunganController::class, 'getEdukasiFarmasi']);

    // pelayanan meso
    Route::post('/simpan-meso', [KunjunganController::class, 'simpanMeso']);
    Route::get('/get-meso', [KunjunganController::class, 'getMeso']);

    // pelayanan penilaian obat luar
    Route::post('/simpan-penilaian-obat-luar', [KunjunganController::class, 'simpanPenilaianObatLuar']);
    Route::get('/get-penilaian-obat-luar', [KunjunganController::class, 'getPenilaianObatLuar']);
});
