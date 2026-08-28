<?php

use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\AsesmenUlangController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/ranap/layanan/asesmenulang'
], function () {
    Route::get('/list', [AsesmenUlangController::class, 'index']);
    Route::post('/simpan-jatuh', [AsesmenUlangController::class, 'simpanJatuh']);
    Route::post('/hapus-jatuh', [AsesmenUlangController::class, 'hapusJatuh']);
    Route::post('/simpan-nyeri', [AsesmenUlangController::class, 'simpanNyeri']);
    Route::post('/hapus-nyeri', [AsesmenUlangController::class, 'hapusNyeri']);
    Route::post('/simpan-pasca-jatuh', [AsesmenUlangController::class, 'simpanPascaJatuh']);
    Route::post('/hapus-pasca-jatuh', [AsesmenUlangController::class, 'hapusPascaJatuh']);
    Route::post('/simpan-penyakit-menular', [AsesmenUlangController::class, 'simpanPenyakitMenular']);
    Route::post('/hapus-penyakit-menular', [AsesmenUlangController::class, 'hapusPenyakitMenular']);
});
