<?php

use App\Http\Controllers\Api\Simrs\Pelayanan\Pediatri\PediatriController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan/pediatri'
], function () {
    Route::post('/store', [PediatriController::class, 'store']);
    Route::post('/deletedata', [PediatriController::class, 'deletedata']);
});
