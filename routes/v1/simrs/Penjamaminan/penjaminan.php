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
    Route::get('/prosedur-idrg', [Klaim::class, 'cariProsedurIdrg']);
    Route::get('/diagnosa-idrg/get', [Klaim::class, 'getDiagnosaIdrg']);
    Route::get('/prosedur-idrg/get', [Klaim::class, 'getProsedurIdrg']);
    Route::post('/diagnosa-idrg', [Klaim::class, 'simpanDiagnosaIdrg']);
    Route::post('/prosedur-idrg', [Klaim::class, 'simpanProsedurIdrg']);
    Route::put('/prosedur-idrg', [Klaim::class, 'ubahJumlahProsedurIdrg']);
    Route::delete('/prosedur-idrg', [Klaim::class, 'hapusProsedurIdrg']);
    Route::delete('/diagnosa-idrg', [Klaim::class, 'hapusDiagnosaIdrg']);
    Route::post('/new-claim', [Klaim::class, 'newClaim']);
    Route::post('/grouping-idrg', [Klaim::class, 'groupingIdrg']);
    Route::post('/terimapasien', [Klaim::class, 'terimapasien']);
});
