<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang\ReturkepbfController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/farmasinew/retur'
], function () {
    Route::get('/perusahaan', [ReturkepbfController::class, 'cariPerusahaan']);
    Route::get('/obat', [ReturkepbfController::class, 'cariObat']);
    Route::get('/ambil-data', [ReturkepbfController::class, 'ambilData']);

    Route::post('/simpan', [ReturkepbfController::class, 'simpanretur']);
    // Route::post('/simpan-header', [PemfakturanController::class, 'simpanHeader']);


});
