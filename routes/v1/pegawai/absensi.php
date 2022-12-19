<?php

use App\Http\Controllers\Api\Pegawai\Absensi\JadwalController;
use App\Http\Controllers\Api\Pegawai\Absensi\TransaksiAbsenController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'pegawai/absensi'
], function () {
    Route::get('/index', [TransaksiAbsenController::class, 'index']);
    Route::get('/rekap', [TransaksiAbsenController::class, 'rekap']);

    // Hapus Jadwal
    Route::post('/hapus-jadwal', [JadwalController::class, 'destroy']);
});
