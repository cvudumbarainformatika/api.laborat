<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\PerencanaanpembelianController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew'
], function () {
    // Route::get('/dialogperencanaanobat', [PerencanaanpembelianController::class, 'perencanaanpembelian']);
    Route::get('/dialogperencanaanobat', [PerencanaanpembelianController::class, 'ambilRencana']);
    Route::get('/dialogperencanaanobatdetail', [PerencanaanpembelianController::class, 'viewrinci']);
    Route::post('/simpanperencanaanbeliobat', [PerencanaanpembelianController::class, 'simpanrencanabeliobat']);
    Route::get('/listrencanabeli', [PerencanaanpembelianController::class, 'listrencanabeli']);
    Route::post('/kuncirencana', [PerencanaanpembelianController::class, 'kuncirencana']);

    Route::post('/update-rinci', [PerencanaanpembelianController::class, 'updateRinci']);
    Route::post('/hapus-head', [PerencanaanpembelianController::class, 'hapusHead']);
    Route::post('/hapus-rinci', [PerencanaanpembelianController::class, 'hapusRinci']);
});
