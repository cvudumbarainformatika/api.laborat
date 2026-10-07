<?php

use App\Http\Controllers\Api\Simrs\HomeCare\CssdRequestController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/homecare/cssd',
], function () {
    Route::get('/barang', [CssdRequestController::class, 'barang']);
    Route::get('/permintaan', [CssdRequestController::class, 'index']);
    Route::get('/permintaan/{nopermintaan}', [CssdRequestController::class, 'show']);
    Route::post('/permintaan', [CssdRequestController::class, 'store']);
    Route::delete('/permintaan/item/{id}', [CssdRequestController::class, 'destroy']);
});
