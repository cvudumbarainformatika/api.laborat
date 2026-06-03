<?php

use App\Http\Controllers\Api\Simrs\Master\PegawaiController;
use App\Http\Controllers\Api\Simrs\Master\PoliController;
use Illuminate\Support\Facades\Route;



Route::group([
  // 'middleware' => 'auth:api',
  'middleware' => 'api.antrian',
  'prefix' => 'antrian'
], function () {
  Route::get('/listdokters', [PegawaiController::class, 'listdokters']);
  // Route::get('/getLayananAll', [SimrsController::class, 'getLayananAll']);
  Route::get('/listmasterpoli', [PoliController::class, 'listpoli']);
});
