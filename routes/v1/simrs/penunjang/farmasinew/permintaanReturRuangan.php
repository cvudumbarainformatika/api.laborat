<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\PermintaanReturRuanganController;
use Illuminate\Support\Facades\Route;

Route::group([
  'middleware' => 'auth:api',
  // 'middleware' => 'jwt.verify',
  'prefix' => 'simrs/farmasinew/depo/permintaan-retur'
], function () {
  Route::get('/list-permintaan', [PermintaanReturRuanganController::class, 'listPermintaan']);
  Route::post('/get-oabt-keluar', [PermintaanReturRuanganController::class, 'getObatKeluar']);
  Route::post('/simpan-permintaan', [PermintaanReturRuanganController::class, 'simpanPermintaan']);
});
