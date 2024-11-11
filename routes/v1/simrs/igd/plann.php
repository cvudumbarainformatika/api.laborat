<?php

use App\Http\Controllers\Api\Simrs\Igd\PlannController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/planing/igd'
],function () {
    Route::post('/simpanranap', [PlannController::class, 'simpanranap']);
});

