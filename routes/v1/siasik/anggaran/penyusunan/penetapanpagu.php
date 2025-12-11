<?php

use App\Http\Controllers\Api\Siasik\Akuntansi\Laporan\LpsalController;
use App\Http\Controllers\Api\Siasik\Akuntansi\SaldoawalController;
use App\Http\Controllers\Api\Siasik\Anggaran\CetakAnggaranController;
use App\Http\Controllers\Api\Siasik\Anggaran\PenyusunanAnggaran\AnggaranPendapatanController;
use App\Http\Controllers\Api\Siasik\Anggaran\PenyusunanAnggaran\PenetapanPaguController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'anggaran/penyusunan/penetapanpagu'
], function () {
    Route::get('/index', [PenetapanPaguController::class, 'index']);
    Route::post('/save', [PenetapanPaguController::class, 'save']);
    Route::post('/delete', [PenetapanPaguController::class, 'delete']);
    Route::post('/kunci', [PenetapanPaguController::class, 'kunci']);

});
