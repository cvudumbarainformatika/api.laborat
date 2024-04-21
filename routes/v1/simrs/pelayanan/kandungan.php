<?php

use App\Http\Controllers\Api\Simrs\Pelayanan\Kandungan\KandunganController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan/kandungan'
], function () {
    Route::post('/store', [KandunganController::class, 'store']);
    Route::post('/deletedata', [KandunganController::class, 'deletedata']);
});
