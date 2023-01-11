<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\StockController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'stok'
], function () {
    Route::get('/current-by-gudang', [StockController::class, 'currentStokByGudang']);
    Route::get('/stok-sekarang', [StockController::class, 'stokSekarang']);
    Route::get('/all-current', [StockController::class, 'currentStok']);
    Route::get('/all-current-has-stok', [StockController::class, 'currentHasStok']);
    Route::get('/current-gudang', [StockController::class, 'currentStokGudang']);
    Route::get('/current-non-ruangan', [StockController::class, 'stokNonRuangan']);
    Route::get('/current-ruangan', [StockController::class, 'stokRuangan']);
    Route::post('/min-max-depo', [StockController::class, 'stokMinMaxDepo']);
    Route::post('/current-by-ruangan', [StockController::class, 'currentStokByRuangan']);
    Route::post('/current-by-permintaan', [StockController::class, 'currentStokByPermintaan']);
    Route::post('/current-by-barang', [StockController::class, 'currentStokByBarang']);
    Route::get('/get-current-by-ruangan', [StockController::class, 'stokByRuangan']);
});
