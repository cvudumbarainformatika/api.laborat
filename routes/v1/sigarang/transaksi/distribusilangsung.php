<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\DistribusiLangsungController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi/distribusilangsung'
], function () {
    Route::get('/index', [DistribusiLangsungController::class, 'index']);
    Route::get('/get-stok-depo', [DistribusiLangsungController::class, 'getStokDepo']);
    Route::get('/get-ruang', [DistribusiLangsungController::class, 'getRuang']);
    Route::post('/store', [DistribusiLangsungController::class, 'store']);
});
