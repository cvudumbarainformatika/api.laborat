<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang\KonsinyasiController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang\PemfakturanController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/farmasinew/gudang'
], function () {
    Route::get('/list-pemakaian-konsinyasi', [KonsinyasiController::class, 'getListPemakaianKonsinyasi']);
    //pemfakturan
    Route::get('/list-belum-faktur', [PemfakturanController::class, 'getPenerimaanBelumAdaFaktur']);
    Route::post('/simpan', [PemfakturanController::class, 'simpan']);
    Route::post('/simpan-header', [PemfakturanController::class, 'simpanHeader']);
});
