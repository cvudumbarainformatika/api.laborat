<?php

use App\Http\Controllers\Api\Pegawai\Absensi\JadwalController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'pegawai/absensi/jadwal'
], function () {
    Route::get('/index', [JadwalController::class, 'index']);
    Route::get('/by-user', [JadwalController::class, 'getByUser']);
    Route::get('/kategori', [JadwalController::class, 'getKategories']);
    Route::get('/hari', [JadwalController::class, 'getDays']);
    Route::post('/store', [JadwalController::class, 'store']);
    Route::post('/destroy', [JadwalController::class, 'destroy']);
});
