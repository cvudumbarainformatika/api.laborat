<?php

use App\Http\Controllers\Api\v1\RsudLiveController;
use Illuminate\Support\Facades\Route;

Route::prefix('rsud-live')->group(function () {
    Route::get('/status', [RsudLiveController::class, 'status']);
    Route::get('/jadwal-dokter-live', [RsudLiveController::class, 'jadwalDokterLive']);
    Route::get('/antrean-live/{noreg_atau_norm}', [RsudLiveController::class, 'antreanLive']);
    Route::get('/pasca-rawat/{norm}', [RsudLiveController::class, 'pascaRawat']);
});
