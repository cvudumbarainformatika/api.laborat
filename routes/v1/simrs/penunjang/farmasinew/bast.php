<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Bast\BastController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Bast\PembebasanpajakController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/bast'
], function () {
    Route::get('/dialogsp', [BastController::class, 'dialogsp']);
    Route::get('/dialogpenerimaan', [BastController::class, 'dialogpenerimaan']);
    Route::post('/simpanbast', [BastController::class, 'simpanbast']);

    Route::get('/dialogsppajak', [PembebasanpajakController::class, 'dialogsppajak']);
});
