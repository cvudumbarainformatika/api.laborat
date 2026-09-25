<?php

use App\Http\Controllers\Api\Simrs\Pendaftaran\Homecare\PendaftaranHomeCareController;
use App\Http\Controllers\Api\Simrs\Kasir\HomecareController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran/homecare'
], function () {
    Route::get('/admin-home-care', [PendaftaranHomeCareController::class, 'layananAdminHomeCare']);
    Route::get('/dokter', [PendaftaranHomeCareController::class, 'getDokter']);
    Route::get('/list', [PendaftaranHomeCareController::class, 'listKunjungan']);
    Route::get('/rincian-pembayaran', [HomecareController::class, 'rincianPembayaran']);
    Route::get('/riwayat-pembayaran', [HomecareController::class, 'riwayatPembayaran']);
    Route::post('/simpan-pembayaran', [HomecareController::class, 'simpanPembayaran']);
    Route::post('/hapus-pembayaran', [HomecareController::class, 'hapusPembayaran']);
    Route::post('/cetak-kwitansi', [HomecareController::class, 'cetakKwitansi']);
    Route::get('/riwayat-kwitansi', [HomecareController::class, 'riwayatKwitansi']);
    Route::get('/cek-kwitansi-pembayaran', [HomecareController::class, 'cekKwitansiPembayaran']);
    Route::post('/batal-kwitansi', [HomecareController::class, 'batalKwitansi']);
    Route::post('/simpan-daftar', [PendaftaranHomeCareController::class, 'simpanKunjungan']);
    Route::post('/berangkat', [PendaftaranHomeCareController::class, 'berangkat']);
});
