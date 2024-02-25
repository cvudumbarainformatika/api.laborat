<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/penunjang/farmasinew/obatoperasi'
], function () {
    Route::get('/get-permintaan', [PersiapanOperasiController::class, 'getPermintaan']);
    Route::get('/get-permintaan-for-dokter', [PersiapanOperasiController::class, 'getPermintaanForDokter']);
    Route::post('/simpan-permintaan', [PersiapanOperasiController::class, 'simpanPermintaan']);
    Route::post('/distribusi', [PersiapanOperasiController::class, 'simpanDistribusi']);
    Route::post('/terima-pengembalian', [PersiapanOperasiController::class, 'terimaPengembalian']);
    Route::post('/simpan-resep', [PersiapanOperasiController::class, 'simpanEresep']);
    Route::post('/selesai-resep', [PersiapanOperasiController::class, 'selesaiEresep']);
});
