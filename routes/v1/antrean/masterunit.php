<?php

use App\Http\Controllers\Api\Antrean\master\UnitController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'masterunit'
], function () {
    Route::get('/data', [UnitController::class, 'index']);
    // Route::get('/synch', [PoliController::class, 'synch']);
    // Route::post('/store', [PoliController::class, 'store']);
    // Route::post('/destroy', [PoliController::class, 'destroy']);
});
