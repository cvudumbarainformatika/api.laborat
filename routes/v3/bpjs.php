<?php

use App\Http\Controllers\Api\Mjkn\StatuslayananController;
use Illuminate\Support\Facades\Route;



Route::group([
    // 'middleware' => 'auth:api',
    'middleware' => 'jkn.auth',
    'prefix' => 'mjkn'
], function () {
    Route::post('/status-antrean', [StatuslayananController::class, 'byLayanan']);  //mJkn (2)
});
