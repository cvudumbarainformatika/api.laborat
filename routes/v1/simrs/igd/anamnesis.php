<?php

use App\Http\Controllers\Api\Simrs\Igd\AnamnesisController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan/igd/anamnesis'
],function () {
    Route::post('/simpananamnesis', [AnamnesisController::class, 'simpananamnesis']);

});

