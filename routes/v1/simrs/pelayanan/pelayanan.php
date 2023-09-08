<?php

use App\Http\Controllers\Api\Simrs\Bridgingeklaim\EwseklaimController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Anamnesis\AnamnesisController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Diagnosa\DiagnosatransController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Pemeriksaanfisik\PemeriksaanfisikController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Tindakan\TindakanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan'
], function () {
    Route::post('/simpananamnesis', [AnamnesisController::class, 'simpananamnesis']);
    Route::post('/hapusanamnesis', [AnamnesisController::class, 'hapusanamnesis']);
    Route::get('/historyanamnesis', [AnamnesisController::class, 'historyanamnesis']);

    Route::post('/simpanpemeriksaanfisik', [PemeriksaanfisikController::class, 'simpan']);
    Route::post('/hapuspemeriksaanfisik', [PemeriksaanfisikController::class, 'hapuspemeriksaanfisik']);
    Route::post('/simpangambar', [PemeriksaanfisikController::class, 'simpangambar']);
    Route::post('/hapusgambar', [PemeriksaanfisikController::class, 'hapusgambar']);

    Route::post('/hapusdiagnosa', [DiagnosatransController::class, 'hapusdiagnosa']);
    Route::post('/simpandiagnosa', [DiagnosatransController::class, 'simpandiagnosa']);
    Route::get('/listdiagnosa', [DiagnosatransController::class, 'listdiagnosa']);

    Route::get('/dialogtindakanpoli', [TindakanController::class, 'dialogtindakanpoli']);
    Route::post('/simpantindakanpoli', [TindakanController::class, 'simpantindakanpoli']);

    Route::post('/ewseklaimrajal_newclaim', [EwseklaimController::class, 'ewseklaimrajal_newclaim']);
    Route::get('/caridiagnosa', [EwseklaimController::class, 'caridiagnosa']);
    Route::get('/carisimulasi', [EwseklaimController::class, 'carisimulasi']);

    Route::get('/cariprocedure', [EwseklaimController::class, 'cariprocedure']);
});
