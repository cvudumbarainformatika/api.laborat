<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\StockController;
use Illuminate\Support\Facades\Route;

Route::group([
  'middleware' => 'api',
  'prefix' => 'stok'
], function () {
  Route::post('/min-max-depo', [StockController::class, 'stokMinMaxDepo']);
});
