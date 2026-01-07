<?php

use App\Http\Controllers\Api\Simrs\Master\Tarif\TindakanOperasiController;
use Illuminate\Support\Facades\Route;


Route::group([
  'middleware' => 'auth:api',
  // 'middleware' => 'jwt.verify',
  'prefix' => 'simrs/master/tarif/tindakan-operasi'
], function () {
  Route::get('/list', [TindakanOperasiController::class, 'list']);
  Route::post('/simpan', [TindakanOperasiController::class, 'simpan']); // diputer dulu ke tabel sementara 
  Route::post('/hapus', [TindakanOperasiController::class, 'hidden']);
  Route::post('/tampilkan', [TindakanOperasiController::class, 'showAgain']);
});
