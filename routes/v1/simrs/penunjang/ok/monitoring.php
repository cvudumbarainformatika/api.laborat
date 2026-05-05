<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi\MonitoringController;
use Illuminate\Support\Facades\Route;


Route::group([
  'middleware' => 'auth:api',
  'prefix' => 'simrs/penunjang/ok/monitoring'
], function () {
  // selema
  Route::get('/selama/get', [MonitoringController::class, 'getSelama']);
  Route::post('/selama/simpan', [MonitoringController::class, 'simpanSelama']);
  Route::post('/selama/simpan-medikasi', [MonitoringController::class, 'simpanMedikasiSelama']);
  // pasca
  Route::get('/pasca/get', [MonitoringController::class, 'getLogPasca']);
  Route::post('/pasca/simpan', [MonitoringController::class, 'simpanLogPasca']);
  Route::post('/pasca/simpan-medikasi', [MonitoringController::class, 'simpanMedikasiPasca']);
});
