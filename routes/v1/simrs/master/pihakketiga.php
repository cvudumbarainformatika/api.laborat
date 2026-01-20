<?php

use App\Http\Controllers\Api\Simrs\Master\PihakKetigaController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'master/pihakketiga'
], function () {
    Route::get('/index',[PihakKetigaController::class, 'index']);
    Route::post('/save',[PihakKetigaController::class, 'save']);
});
