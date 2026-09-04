<?php

use App\Http\Controllers\Api\Satusehat\Bundle\KunjunganController;
use App\Http\Controllers\Api\Satusehat\DashboardSatsetController;
use Illuminate\Support\Facades\Route;

Route::group([
    // 'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'satusehat/bundle'
], function () {
    Route::get('/kirim-kunjungan', [KunjunganController::class, 'index']);

    Route::get('/cek-medication', [KunjunganController::class, 'medication']);
    Route::get('/cari-loinc', [KunjunganController::class, 'cari_loinc']);

    Route::get('/coba-bundle', [KunjunganController::class, 'index']);

    // Dashboard & Laporan Pengiriman SatuSehat
    Route::get('/dashboard/summary', [DashboardSatsetController::class, 'summary']);
    Route::get('/dashboard/resource-stats', [DashboardSatsetController::class, 'resourceStats']);
    Route::get('/dashboard/error-stats', [DashboardSatsetController::class, 'errorStats']);
    Route::get('/dashboard/list-error', [DashboardSatsetController::class, 'listError']);
    Route::get('/dashboard/list-kunjungan', [DashboardSatsetController::class, 'listKunjungan']);
    Route::get('/dashboard/detail-kunjungan', [DashboardSatsetController::class, 'detailKunjungan']);
    Route::post('/dashboard/retry', [DashboardSatsetController::class, 'retry']);
});
