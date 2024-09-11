<?php

use App\Http\Controllers\Api\Simrs\Igd\IgdController;
use App\Http\Controllers\Api\Simrs\Igd\TriageController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan/igd'
],function () {
    Route::post('/terimapasien', [IgdController::class, 'terimapasien']);
    Route::post('/simpantriage', [TriageController::class, 'simpantriage']);

    Route::post('/hapustriage', [TriageController::class, 'hapustriage']);
});

