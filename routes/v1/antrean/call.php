<?php

use App\Http\Controllers\Api\Antrean\CallController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'call'
], function () {
    Route::get('/data', [CallController::class, 'index']);
    Route::get('/calling-layanan', [CallController::class, 'calling_layanan']);
});
