<?php

use App\Http\Controllers\Api\Antrean\master\VideoController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'video'
], function () {
    Route::get('/data', [VideoController::class, 'index']);
    // Route::get('/synch', [PoliController::class, 'synch']);
    // Route::post('/store', [DisplayController::class, 'store']);
    // Route::post('/destroy', [DisplayController::class, 'destroy']);
});
