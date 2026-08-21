<?php

use App\Http\Controllers\Api\Simrs\Pelayanan\LaporanEswlController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/pelayanan/laporaneswl'
], function () {
    Route::post('/simpan', [LaporanEswlController::class, 'simpan']);
    Route::post('/hapus', [LaporanEswlController::class, 'hapus']);
});
