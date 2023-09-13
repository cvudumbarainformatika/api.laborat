<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Laporan\LaporanPenerimaanController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'laporan/penerimaan'
], function () {
    Route::get('/lappenerimaan', [LaporanPenerimaanController::class, 'lappenerimaan']);
});
