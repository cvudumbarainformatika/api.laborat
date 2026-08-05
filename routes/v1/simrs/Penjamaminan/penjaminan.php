<?php

use App\Http\Controllers\Api\Simrs\Penjaminan\Klaim;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penjaminan/klaim'
], function () {
    Route::get('/getdataklaim', [Klaim::class, 'getdataklaim']);
    Route::get('/cara-masuk', [Klaim::class, 'caraMasuk']);
    Route::get('/kunjungan-klaim', [Klaim::class, 'kunjunganKlaim']);
    Route::get('/tarif', [Klaim::class, 'tarif']);
    Route::get('/diagnosa-idrg', [Klaim::class, 'cariDiagnosaIdrg']);
    Route::post('/new-claim', [Klaim::class, 'newClaim']);
    Route::post('/terimapasien', [Klaim::class, 'terimapasien']);
});
