<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\CaripasienController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\DepoController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\EresepController;
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
    // Route::post('/resepkeluar', [ResepkeluarController::class, 'cekResepKeluar']);
    Route::get('/listresep', [ResepkeluarController::class, 'listresep']);
    Route::get('/get-signa', [ResepkeluarController::class, 'ambilSigna']);

    Route::post('/hapusobat', [ResepkeluarController::class, 'hapusobat']);

    Route::get('/listjenisresep', [ResepkeluarController::class, 'listjenisresep']);

    //--------------ERESEP----------------//
    Route::get('/lihatstokobateresepBydokter', [EresepController::class, 'lihatstokobateresepBydokter']);
    Route::post('/pembuatanresep', [EresepController::class, 'pembuatanresep']);
    Route::get('/listresepbydokter', [EresepController::class, 'listresepbydokter']);
    Route::post('/kirimresep', [EresepController::class, 'kirimresep']);
    Route::get('/conterracikan', [EresepController::class, 'conterracikan']);
});
