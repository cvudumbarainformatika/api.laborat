<?php

use App\Http\Controllers\Api\Simrs\Master\Tarif\TarifRadiologiController;
use Illuminate\Support\Facades\Route;


Route::group([
  'middleware' => 'auth:api',
  // 'middleware' => 'jwt.verify',
  'prefix' => 'simrs/master/tarif/radiologi'
], function () {
  Route::get('/list', [TarifRadiologiController::class, 'list']);
  Route::get('/type', [TarifRadiologiController::class, 'tipe']);
  Route::post('/simpan', [TarifRadiologiController::class, 'simpan']); // diputer dulu ke tabel sementara 
  Route::post('/hapus', [TarifRadiologiController::class, 'hidden']);
  Route::post('/tampilkan', [TarifRadiologiController::class, 'showAgain']);
});
