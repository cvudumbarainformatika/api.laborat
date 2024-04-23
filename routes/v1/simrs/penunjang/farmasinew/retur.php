<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang\ReturkepbfController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/farmasinew/retur'
], function () {
    Route::get('/perusahaan', [ReturkepbfController::class, 'cariPerusahaan']);
    // Route::get('/list-belum-faktur', [PemfakturanController::class, 'getPenerimaanBelumAdaFaktur']);

    // Route::post('/simpan', [PemfakturanController::class, 'simpan']);
    // Route::post('/simpan-header', [PemfakturanController::class, 'simpanHeader']);


});
