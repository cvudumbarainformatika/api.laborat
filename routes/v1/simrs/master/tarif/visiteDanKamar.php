<?php

use App\Http\Controllers\Api\Simrs\Master\RsTigaPuluhTarifController;
use Illuminate\Support\Facades\Route;


Route::group([
  'middleware' => 'auth:api',
  // 'middleware' => 'jwt.verify',
  'prefix' => 'simrs/master/tarif/visite-dan-kamar'
], function () {
  Route::get('/list', [RsTigaPuluhTarifController::class, 'list']);
  Route::post('/simpan', [RsTigaPuluhTarifController::class, 'simpan']); // diputer dulu ke tabel sementara 
  Route::post('/hapus', [RsTigaPuluhTarifController::class, 'hidden']);
  Route::post('/tampilkan', [RsTigaPuluhTarifController::class, 'showAgain']);
});
