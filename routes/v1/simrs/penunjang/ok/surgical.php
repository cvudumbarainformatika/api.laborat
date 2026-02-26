<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi\SurgicalSafetyController;
use Illuminate\Support\Facades\Route;

Route::group([
  'middleware' => 'auth:api',
  // 'middleware' => 'jwt.verify',
  'prefix' => 'simrs/penunjang/surgical'
], function () {
  Route::get('/get-nakes', [SurgicalSafetyController::class, 'getNakes']);
  Route::post('/simpan', [SurgicalSafetyController::class, 'store']);
  // implant
  Route::get('/get-implant', [SurgicalSafetyController::class, 'getImplat']);
  Route::post('/simpan-implant', [SurgicalSafetyController::class, 'simpanImplat']);
  Route::post('/simpan-gambar', [SurgicalSafetyController::class, 'simpanGambar']);
  Route::post('/hapus-gambar', [SurgicalSafetyController::class, 'hapusGambar']);
  // kasa cssd
  Route::get('/get-master-kasa', [SurgicalSafetyController::class, 'masterCssd']);
  Route::post('/simpan-inventaris-kasa', [SurgicalSafetyController::class, 'simpanInventarisKasa']);
  Route::post('/hapus-inventaris-kasa', [SurgicalSafetyController::class, 'hapusInventarisKasa']);
  // Instrumen
  Route::post('/simpan-inventaris-Instrumen', [SurgicalSafetyController::class, 'simpanInventarisInstrumen']);
  // Route::post('/hapus-inventaris-Instrumen', [SurgicalSafetyController::class, 'hapusInventarisInstrumen']);
});
