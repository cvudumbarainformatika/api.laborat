<?php

use App\Http\Controllers\Api\Simrs\UnitPelayananArsip\PeminjamanBerkasController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/unitpengelolaharsip/peminjaman'
], function () {
    Route::get('/cari-arsip', [PeminjamanBerkasController::class, 'cariarsip']);
    Route::get('/data-pegawai', [PeminjamanBerkasController::class, 'getdatapegawai']);
    Route::post('/simpan-pinjaman', [PeminjamanBerkasController::class, 'simpanpeminjaman']);
    Route::get('/list-peminjaman', [PeminjamanBerkasController::class, 'getlistpeminjaman']);
    Route::post('/simpan-kembali', [PeminjamanBerkasController::class, 'simpankembali']);
     Route::get('/cari-pegawai', [PeminjamanBerkasController::class, 'caripegawai']);
});

