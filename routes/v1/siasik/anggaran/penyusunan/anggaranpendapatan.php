<?php

use App\Http\Controllers\Api\Siasik\Akuntansi\Laporan\LpsalController;
use App\Http\Controllers\Api\Siasik\Akuntansi\SaldoawalController;
use App\Http\Controllers\Api\Siasik\Anggaran\CetakAnggaranController;
use App\Http\Controllers\Api\Siasik\Anggaran\PenyusunanAnggaran\AnggaranPendapatanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'anggaran/penyusunan/anggaranpendapatan'
], function () {
    Route::get('/index', [AnggaranPendapatanController::class, 'index']);
    Route::post('/save', [AnggaranPendapatanController::class, 'save']);
    Route::get('/getrekening', [AnggaranPendapatanController::class, 'getRekening']);
    Route::post('/delete', [AnggaranPendapatanController::class, 'delete']);
    Route::post('/kunci', [AnggaranPendapatanController::class, 'kunci']);

});
