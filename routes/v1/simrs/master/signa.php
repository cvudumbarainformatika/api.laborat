<?php

use App\Http\Controllers\Api\Simrs\Master\SignaController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/master/signa'
], function () {
    Route::get('/get-signa', [SignaController::class, 'getSigna']);
    Route::post('/store-signa', [SignaController::class, 'store']);
});
