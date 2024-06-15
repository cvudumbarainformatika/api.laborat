<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/penunjang/farmasinew/obatoperasi'
], function () {
    Route::get('/get-permintaan', [PersiapanOperasiController::class, 'getPermintaan']);
    Route::get('/get-permintaan-for-dokter', [PersiapanOperasiController::class, 'getPermintaanForDokter']);
    Route::get('/get-obat-persiapan', [PersiapanOperasiController::class, 'getObatPersiapan']);

    Route::post('/simpan-permintaan', [PersiapanOperasiController::class, 'simpanPermintaan']);
    Route::post('/hapus-obat-permintaan', [PersiapanOperasiController::class, 'hapusObatPermintaan']);
    Route::post('/selesai-obat-permintaan', [PersiapanOperasiController::class, 'selesaiObatPermintaan']);

    Route::post('/distribusi', [PersiapanOperasiController::class, 'simpanDistribusi']);
    Route::post('/tambah-distribusi', [PersiapanOperasiController::class, 'tambahDistribusi']);
    Route::post('/terima-pengembalian', [PersiapanOperasiController::class, 'terimaPengembalian']);
    Route::post('/simpan-resep', [PersiapanOperasiController::class, 'simpanEresep']);
    Route::post('/selesai-resep', [PersiapanOperasiController::class, 'selesaiEresep']);
    Route::post('/batal-obat-resep', [PersiapanOperasiController::class, 'batalObatResep']);

    Route::post('/batal-operasi', [PersiapanOperasiController::class, 'batalOperasi']);
});
