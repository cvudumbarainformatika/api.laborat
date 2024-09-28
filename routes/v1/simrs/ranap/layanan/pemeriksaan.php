<?php

use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\AnamnesisController;
use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\PemeriksaanUmumController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/layanan/pemeriksaan'
], function () {
    // Route::post('/simpananamnesis', [AnamnesisController::class, 'simpananamnesis']);
    Route::get('/pemeriksaanumum', [PemeriksaanUmumController::class, 'list']);
});
