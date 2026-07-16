<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi\PembatalanPelayananController;
use Illuminate\Support\Facades\Route;

Route::group([
  'middleware' => 'auth:api',
  'prefix' => 'simrs/penunjang/pembatalan-operasi'
], function () {
  Route::get('/get-pembatalan', [PembatalanPelayananController::class, 'getPembatalan']);
  Route::post('/simpan-pembatalan', [PembatalanPelayananController::class, 'store']);
});
