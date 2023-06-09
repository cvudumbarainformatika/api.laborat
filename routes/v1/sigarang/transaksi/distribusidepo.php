<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\DistribusiDepoController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi/distribusidepo'
], function () {
    Route::get('/index', [DistribusiDepoController::class, 'index']);
    Route::get('/penerimaan', [DistribusiDepoController::class, 'penerimaan']);
    Route::get('/distribusi', [DistribusiDepoController::class, 'getDistribusi']);
    Route::get('/to-distribute', [DistribusiDepoController::class, 'toDistribute']);
    Route::post('/store', [DistribusiDepoController::class, 'store']);
    Route::post('/terima-depo', [DistribusiDepoController::class, 'diterimaDepo']);
    Route::post('/hapus-data-gudang', [DistribusiDepoController::class, 'hapusDataStokGudang']);
    Route::get('/destroy', [DistribusiDepoController::class, 'destroy']);
});
