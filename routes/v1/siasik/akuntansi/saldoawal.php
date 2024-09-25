<?php

use App\Http\Controllers\Api\Siasik\Akuntansi\Laporan\LpsalController;
use App\Http\Controllers\Api\Siasik\Akuntansi\SaldoawalController;
use Illuminate\Support\Facades\Route;

Route::group([
    // 'middleware' => 'auth:api',
    'prefix' => 'akuntansi/saldoawal'
], function () {
    Route::get('/akunsaldo', [SaldoawalController::class, 'akunsaldo']);
    Route::post('/save', [SaldoawalController::class, 'save']);

});
