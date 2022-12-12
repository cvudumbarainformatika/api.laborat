<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\StockController;
use Illuminate\Support\Facades\Route;

Route::group([
  'middleware' => 'auth:api',
  'prefix' => 'stok'
], function () {
  Route::post('/min-max-depo', [StockController::class, 'stokMinMaxDepo']);
  Route::get('/all-current', [StockController::class, 'currentStok']);
  Route::post('/current-by-ruangan', [StockController::class, 'currentStokByRuangan']);
  Route::post('/current-by-permintaan', [StockController::class, 'currentStokByPermintaan']);
  Route::post('/current-by-barang', [StockController::class, 'currentStokByBarang']);
});
