<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi\AssasementController;
use App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi\KamaroperasiController;
use Illuminate\Support\Facades\Route;


Route::group([
  'middleware' => 'auth:api',
  'prefix' => 'simrs/penunjang/ok/assasement'
], function () {
  Route::get('/getnota', [KamaroperasiController::class, 'getnota']);
  Route::get('/pra-bedah/ambil', [AssasementController::class, 'ambil']);
  Route::post('/pra-bedah/simpan', [AssasementController::class, 'simpan']);
  Route::post('/pra-induksi/simpan', [AssasementController::class, 'simpanPraInduksi']);
});
