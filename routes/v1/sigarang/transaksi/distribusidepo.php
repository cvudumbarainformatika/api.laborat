<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\DistribusiDepoController;
use Illuminate\Support\Facades\Route;


Route::group([
  'middleware' => 'auth:api',
  // 'middleware' => 'jwt.verify',
  'prefix' => 'transaksi/distribusidepo'
], function () {
  Route::get('/index', [DistribusiDepoController::class, 'index']);
  Route::get('/distribusi', [DistribusiDepoController::class, 'getDistribusi']);
  Route::post('/store', [DistribusiDepoController::class, 'store']);
  Route::get('/destroy', [DistribusiDepoController::class, 'destroy']);
});
