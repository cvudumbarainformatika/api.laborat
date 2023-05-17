<?php

use App\Http\Controllers\Api\Simrs\Master\BridgingbpjsController;
use App\Http\Controllers\Api\Simrs\Master\listsepController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran'
], function () {
    Route::post('/cekpesertabpjs', [BridgingbpjsController::class, 'cekpsertabpjs']);
    Route::post('/listrujukanpcare', [BridgingbpjsController::class, 'listrujukanpcare']);
    Route::post('/listrujukanrs', [BridgingbpjsController::class, 'listrujukanrs']);
    Route::post('/listsepmrs', [listsepController::class, 'listsepmrs']);
    Route::post('/diagnosabybpjs', [BridgingbpjsController::class, 'diagnosabybpjs']);
});
