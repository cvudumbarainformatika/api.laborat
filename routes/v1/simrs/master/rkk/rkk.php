<?php

use App\Http\Controllers\Api\Simrs\Master\Rkk\MasterRkkController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master/rkk'
], function () {
    Route::get('/getall', [MasterRkkController::class, 'index']);
    Route::post('/store', [MasterRkkController::class, 'store']);
    Route::post('/delete', [MasterRkkController::class, 'delete']);
});
