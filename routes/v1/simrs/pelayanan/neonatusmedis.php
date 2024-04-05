<?php

// use App\Http\Controllers\Api\Simrs\Pelayanan\Psikiatri\PsikiatriController;

use App\Http\Controllers\Api\Simrs\Pelayanan\Neonatus\NeonatusMedisController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan/neonatusmedis'
], function () {
    Route::post('/store', [NeonatusMedisController::class, 'store']);
    // Route::post('/deletedata', [PsikiatriController::class, 'deletedata']);
});
