<?php

use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Etc\EvaluasiResepController;
use Illuminate\Support\Facades\Route;

Route::group([
  'middleware' => 'auth:api',
  // 'middleware' => 'jwt.verify',
  'prefix' => 'simrs/laporan/farmasi/evaluasi'
], function () {
  Route::get('/get-data', [EvaluasiResepController::class, 'index']);
});
