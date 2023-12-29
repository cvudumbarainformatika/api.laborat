<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\CaripasienController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\DepoController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\LihatStokController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\ResepkeluarController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/depo'
], function () {
    Route::get('/lihatstokgudang', [DepoController::class, 'lihatstokgudang']);
    Route::post('/simpanpermintaandepo', [DepoController::class, 'simpanpermintaandepo']);
    Route::get('/listpermintaandepo', [DepoController::class, 'listpermintaandepo']);
    Route::post('/kuncipermintaan', [DepoController::class, 'kuncipermintaan']);
    Route::post('/terimadistribusi', [DepoController::class, 'terimadistribusi']);

    Route::get('/lihatstokobateresep', [LihatStokController::class, 'lihatstokobateresep']);

    Route::get('/caripasienpoli', [CaripasienController::class, 'caripasienpoli']);
    Route::get('/caripasienranap', [CaripasienController::class, 'caripasienranap']);
    Route::get('/caripasienigd', [CaripasienController::class, 'caripasienigd']);

    Route::post('/resepkeluar', [ResepkeluarController::class, 'resepkeluar']);
    Route::get('/listresep', [ResepkeluarController::class, 'listresep']);

    Route::post('/hapusobat', [ResepkeluarController::class, 'hapusobat']);
});
