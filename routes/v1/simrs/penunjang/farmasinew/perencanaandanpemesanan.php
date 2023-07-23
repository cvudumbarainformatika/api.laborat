<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\PerencanaanpembelianController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew'
], function () {
    Route::get('/dialogperencanaanobat', [PerencanaanpembelianController::class, 'perencanaanpembelian']);
    Route::post('/simpanperencanaanbeliobat', [PerencanaanpembelianController::class, 'simpanrencanabeliobat']);
    Route::get('/listrencanabeli', [PerencanaanpembelianController::class, 'listrencanabeli']);
});
