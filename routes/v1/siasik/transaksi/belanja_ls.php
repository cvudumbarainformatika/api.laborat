<?php

use App\Http\Controllers\Api\Siasik\TransaksiLS\NPD_LSController;
use Illuminate\Support\Facades\Route;


Route::group([
    // 'middleware' => 'auth:api',
    'prefix' => 'transaksi/belanja_ls'
], function () {
    Route::get('/perusahaan', [NPD_LSController::class, 'perusahaan']);
    Route::get('/ptk', [NPD_LSController::class, 'ptk']);
    Route::get('/bast', [NPD_LSController::class, 'bast']);
    Route::get('/simpan', [NPD_LSController::class, 'simpan']);
});
