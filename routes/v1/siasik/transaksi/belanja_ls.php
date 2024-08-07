<?php

use App\Http\Controllers\Api\Siasik\TransaksiLS\NPD_LSController;
use Illuminate\Support\Facades\Route;


Route::group([
    // 'middleware' => 'auth:api',
    'prefix' => 'transaksi/belanja_ls'
], function () {
    Route::get('/perusahaan', [NPD_LSController::class, 'perusahaan']);
    Route::get('/ptk', [NPD_LSController::class, 'ptk']);
    Route::get('/bastfarmasi', [NPD_LSController::class, 'bastfarmasi']);
    Route::post('/simpannpd', [NPD_LSController::class, 'simpannpd']);
});
