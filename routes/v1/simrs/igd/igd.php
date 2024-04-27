<?php

use App\Http\Controllers\Api\Simrs\Igd\IgdController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan/igd'
],function () {
    Route::post('/terimapasien', [IgdController::class, 'terimapasien']);
});

