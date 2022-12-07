<?php

use App\Http\Controllers\Api\Logistik\Sigarang\MinMaxStokController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'minmaxstok'
], function () {
    Route::get('/index', [MinMaxStokController::class, 'index']);
    Route::get('/minmaxstok', [MinMaxStokController::class, 'minmaxstok']);
    Route::post('/store', [MinMaxStokController::class, 'store']);
    Route::post('/destroy', [MinMaxStokController::class, 'destroy']);
});
