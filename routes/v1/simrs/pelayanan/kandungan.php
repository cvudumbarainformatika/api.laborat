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
    Route::get('/masterskrining', [KandunganController::class, 'masterskrining']);
    Route::get('/skrining', [KandunganController::class, 'skrining']);
    Route::post('/storeSkrining', [KandunganController::class, 'storeSkrining']);
});
