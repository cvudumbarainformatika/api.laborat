<?php

use App\Http\Controllers\Api\Siasik\TransaksiPanjar\NPD_PanjarController;
use App\Http\Controllers\Api\Siasik\TransaksiPanjar\NPD_UPController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'transaksi/panjar'
], function () {

    // UP //
    Route::get('/index', [NPD_UPController::class, 'index']);
    Route::post('/save', [NPD_UPController::class, 'save']);
    Route::post('/kunci', [NPD_UPController::class, 'kunci']);
    Route::post('/delete', [NPD_UPController::class, 'delete']);
    Route::get('/bendaharapengeluaran', [NPD_UPController::class, 'bendaharapengeluaran']);
    Route::get('/bank', [NPD_UPController::class, 'masterbank']);
    Route::get('/belumverif', [NPD_UPController::class, 'belumVerif']);
    Route::get('/sudahverif', [NPD_UPController::class, 'sudahVerif']);
    Route::post('/verif', [NPD_UPController::class, 'verif']);
    Route::get('/databelumcreate', [NPD_UPController::class, 'databelumcreate']);
    Route::get('/datasudahcreate', [NPD_UPController::class, 'datasudahcreate']);
    Route::post('/createnpk', [NPD_UPController::class, 'createnpk']);


    // NPD PANJAR //
    Route::post('/savenpd', [NPD_PanjarController::class, 'save']);
});
