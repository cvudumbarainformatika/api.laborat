<?php

use App\Http\Controllers\Api\Simrs\Pelayanan\SuratKontrol\SuratKontrolController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan/suratkontrol'
], function () {
    Route::get('/list', [SuratKontrolController::class, 'index']);
    Route::get('/bridging-bpjs-list', [SuratKontrolController::class, 'bpjsList']);
    Route::post('/create', [SuratKontrolController::class, 'create']);
    Route::post('/remove', [SuratKontrolController::class, 'remove']);
    Route::put('/update/{noSuratKontrol}', [SuratKontrolController::class, 'update']);
});
