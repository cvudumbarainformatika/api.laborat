<?php

use App\Http\Controllers\Api\Simrs\Master\Tarif\TarifMasterAmbulanController;
use Illuminate\Support\Facades\Route;


Route::group([
  'middleware' => 'auth:api',
  // 'middleware' => 'jwt.verify',
  'prefix' => 'simrs/master/tarif/ambulan'
], function () {
  Route::get('/list', [TarifMasterAmbulanController::class, 'list']);
  Route::post('/simpan', [TarifMasterAmbulanController::class, 'simpan']); // diputer dulu ke tabel sementara 
  Route::post('/hapus', [TarifMasterAmbulanController::class, 'hidden']);
  Route::post('/tampilkan', [TarifMasterAmbulanController::class, 'showAgain']);
});
