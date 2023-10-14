<?php

use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/sigarang'
], function () {
    Route::get('/lappenerimaan', [LaporanPenerimaanController::class, 'lappenerimaan']);
});
