<?php

use App\Http\Controllers\Api\Simrs\UnitPelayananArsip\DataMapController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/unitpengelolaharsip/map'
], function () {
    Route::get('/list-data', [DataMapController::class, 'listdata']);
    Route::post('/simpan-map', [DataMapController::class, 'simpanmap']);
    Route::post('/simpanisimap', [DataMapController::class, 'simpanisimap']);
    Route::get('/rincian-map', [DataMapController::class, 'rinciandidalammap']);
});

