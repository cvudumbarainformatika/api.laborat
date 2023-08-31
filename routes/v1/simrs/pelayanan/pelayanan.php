<?php

use App\Http\Controllers\Api\Simrs\Bridgingeklaim\EwseklaimController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Anamnesis\AnamnesisController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Pemeriksaanfisik\PemeriksaanfisikController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan'
], function () {
    Route::post('/simpananamnesis', [AnamnesisController::class, 'simpananamnesis']);
    Route::post('/hapusanamnesis', [AnamnesisController::class, 'hapusanamnesis']);

    Route::post('/simpanpemeriksaanfisik', [PemeriksaanfisikController::class, 'simpan']);
    Route::post('/simpangambar', [PemeriksaanfisikController::class, 'simpangambar']);

    Route::post('/ewseklaimrajal_newclaim', [EwseklaimController::class, 'ewseklaimrajal_newclaim']);
});
