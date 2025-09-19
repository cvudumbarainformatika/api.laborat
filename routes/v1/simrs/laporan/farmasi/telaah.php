<?php

use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Etc\TelaahResepController;
use Illuminate\Support\Facades\Route;

Route::group([
  'middleware' => 'auth:api',
  // 'middleware' => 'jwt.verify',
  'prefix' => 'simrs/laporan/farmasi/telaah'
], function () {
  Route::get('/get-data', [TelaahResepController::class, 'getData']);
  Route::get('/get-pegawai', [TelaahResepController::class, 'getPegawai']);
});
