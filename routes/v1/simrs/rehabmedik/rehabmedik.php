<?php

use App\Http\Controllers\Api\Simrs\Rehabmedik\PengkajianController;
use App\Http\Controllers\Api\Simrs\Rehabmedik\PengunjungController;
use App\Http\Controllers\Api\Simrs\Rehabmedik\SoapController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/rehabmedik'
], function () {

    //pengunjung
    Route::get('/kunjunganpasien', [PengunjungController::class, 'index']);
    Route::get('/terimapasien', [PengunjungController::class, 'terimapasien']);

    // pengkajian
    Route::post('/pengkajian/store', [PengkajianController::class, 'store']);
    Route::post('/pengkajian/delete', [PengkajianController::class, 'delete']);

    // Asessment (SOAP)
    Route::post('/soap/store', [SoapController::class, 'store']);
});
