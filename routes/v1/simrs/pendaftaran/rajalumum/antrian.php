<?php

use App\Http\Controllers\Api\Simrs\Antrian\AntrianController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran/antrian'
], function () {

    Route::get('/call_layanan_ruang', [AntrianController::class, 'call_layanan_ruang']);
});
