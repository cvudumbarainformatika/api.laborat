<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\PermintaanruanganController;
use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\VerifPermintaanruanganController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi/permintaanruangan'
], function () {
    Route::get('/draft', [PermintaanruanganController::class, 'draft']);
    Route::get('/get-permintaan', [VerifPermintaanruanganController::class, 'getPerrmintaan']);
    Route::post('/store', [PermintaanruanganController::class, 'store']);
    Route::post('/selesai-input', [PermintaanruanganController::class, 'selesaiInput']);
});
