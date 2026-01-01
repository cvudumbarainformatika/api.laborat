<?php

use App\Http\Controllers\Api\Siasik\Master\MasterJasaLainController;
use App\Http\Controllers\Api\Siasik\Master\PTKController;
use Illuminate\Support\Facades\Route;
Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'master/siasik/jasa'
], function () {
     Route::get('/getrekening', [MasterJasaLainController::class, 'getRekening'])->middleware('throttle:500,1');
     Route::get('/getsatuan', [MasterJasaLainController::class, 'getSatuan'])->middleware('throttle:500,1');
    Route::get('/index', [MasterJasaLainController::class, 'index']);
    Route::post('/save', [MasterJasaLainController::class, 'save']);
    Route::post('/delete', [MasterJasaLainController::class, 'delete']);

});
