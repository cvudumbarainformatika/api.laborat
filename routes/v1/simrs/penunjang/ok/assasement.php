<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi\AssasementController;
use Illuminate\Support\Facades\Route;


Route::group([
  'middleware' => 'auth:api',
  'prefix' => 'simrs/penunjang/ok/assasement'
], function () {
  // Route::get('/getnota', [KamaroperasiController::class, 'getnota']);
  Route::post('/pra-bedah/simpan', [AssasementController::class, 'simpan']);
});
