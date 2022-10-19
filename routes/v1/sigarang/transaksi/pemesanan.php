<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\PemesananController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi/pemesanan'
], function () {
    Route::get('/draft', [PemesananController::class, 'draft']);
    Route::post('/store', [PemesananController::class, 'store']);
    Route::get('/destroy', [PemesananController::class, 'destroy']);
});
