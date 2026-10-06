<?php

use App\Http\Controllers\Api\Simrs\HomeCare\PengunjungController;
use App\Http\Controllers\Api\Simrs\HomeCare\InformConcernController;
use Illuminate\Support\Facades\Route;

Route::group([
  'middleware' => 'auth:api',
  // 'middleware' => 'jwt.verify',
  'prefix' => 'simrs/homecare/pengunjung'
], function () {
  Route::get('/list', [PengunjungController::class, 'listKunjungan']);
  Route::post('/berangkat', [PengunjungController::class, 'berangkat']);
  Route::post('/bukalayanan', [PengunjungController::class, 'bukalayanan']);
  Route::get('/inform-concern', [InformConcernController::class, 'show']);
  Route::post('/inform-concern', [InformConcernController::class, 'save']);
});
