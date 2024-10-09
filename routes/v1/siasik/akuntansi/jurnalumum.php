<?php

use App\Http\Controllers\Api\Siasik\Akuntansi\JurnalUmum\JurnalManualController;
use App\Http\Controllers\Api\Siasik\Akuntansi\JurnalumumController;
use Illuminate\Support\Facades\Route;

Route::group([
    // 'middleware' => 'auth:api',
    'prefix' => 'akuntansi/jurnalumum'
], function () {
    // Route::get('/akunpsap', [JurnalumumController::class, 'akunpsap']);
    // Route::post('/save', [JurnalumumController::class, 'save_ju']);

    Route::get('/permen50', [JurnalManualController::class, 'permen50']);
    Route::get('/jurnalumumotot', [JurnalManualController::class, 'jurnalumumotot']);

});
