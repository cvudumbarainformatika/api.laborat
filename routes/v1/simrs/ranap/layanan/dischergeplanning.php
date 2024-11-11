<?php

use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\DischargePlanningController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/layanan/dischargeplanning'
], function () {

  // Route::get('/getmaster', [HaisController::class, 'getmaster']);
    Route::post('/simpandata', [DischargePlanningController::class, 'simpandata']);
    // Route::get('/pemeriksaanumum', [PemeriksaanUmumController::class, 'list']);

    // Route::get('/penilaian', [PemeriksaanPenilaianController::class, 'list']);
    // Route::post('/penilaian/simpan', [PemeriksaanPenilaianController::class, 'simpan']);
    // Route::post('/hapusdata', [HaisController::class, 'hapusdata']);

});
