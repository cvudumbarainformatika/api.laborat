<?php

use App\Http\Controllers\Api\Simrs\Rehabmedik\PengunjungController;
use App\Http\Controllers\Api\Simrs\Rekammedik\MappingController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/rekammedik/mapping'
], function () {

    Route::get('/tindakan', [MappingController::class, 'index']);
});
