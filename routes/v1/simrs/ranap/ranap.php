<?php

use App\Http\Controllers\Api\Logistik\Sigarang\RuangController;
use App\Http\Controllers\Api\Simrs\Master\Icd9Controller;
use App\Http\Controllers\Api\Simrs\Ranap\RanapController;
use App\Http\Controllers\Api\Simrs\Ranap\RuanganController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/ruangan'
], function () {
    Route::get('/listruanganranap', [RuanganController::class, 'listruanganranap']);
    Route::get('/mastericd9', [Icd9Controller::class, 'mastericd9']);

    Route::get('/kunjunganpasien', [RanapController::class, 'kunjunganpasien']);
    Route::post('/bukalayanan', [RanapController::class, 'bukalayanan']);
});
