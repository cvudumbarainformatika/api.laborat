<?php

use App\Http\Controllers\Api\Siasik\TransaksiLS\NPD_LSController;
use Illuminate\Support\Facades\Route;


Route::group([
    // 'middleware' => 'auth:api',
    'prefix' => 'transaksi/belanja_ls'
], function () {
    Route::get('/listnpdls', [NPD_LSController::class, 'listnpdls']);
    Route::get('/perusahaan', [NPD_LSController::class, 'perusahaan']);
    Route::get('/ptk', [NPD_LSController::class, 'ptk']);
    Route::get('/anggaran', [NPD_LSController::class, 'anggaran']);
    Route::get('/bastfarmasi', [NPD_LSController::class, 'bastfarmasi']);
    Route::post('/simpannpd', [NPD_LSController::class, 'simpannpd']);

    Route::get('/coba', [NPD_LSController::class, 'coba']);

});
