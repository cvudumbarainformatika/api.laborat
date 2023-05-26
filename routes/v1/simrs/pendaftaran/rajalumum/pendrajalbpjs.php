<?php

use App\Http\Controllers\Api\Simrs\Master\BridgingbpjsController;
use App\Http\Controllers\Api\Simrs\Master\listsepController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran'
], function () {
    Route::post('/cekpsertabpjsbynoka', [BridgingbpjsController::class, 'cekpsertabpjsbynoka']);
    Route::post('/cekpsertabpjsbynik', [BridgingbpjsController::class, 'cekpsertabpjsbynik']);
    Route::post('/listrujukanpcare', [BridgingbpjsController::class, 'listrujukanpcare']);
    Route::post('/listrujukanrs', [BridgingbpjsController::class, 'listrujukanrs']);
    Route::post('/listsepmrs', [listsepController::class, 'listsepmrs']);
    Route::post('/diagnosabybpjs', [BridgingbpjsController::class, 'diagnosabybpjs']);
    Route::post('/faskesasalbpjs', [BridgingbpjsController::class, 'faskesasalbpjs']);
    Route::post('/dpjpbpjs', [BridgingbpjsController::class, 'dpjpbpjs']);
});
