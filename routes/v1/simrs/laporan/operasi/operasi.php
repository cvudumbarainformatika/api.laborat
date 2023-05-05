<?php

use App\Http\Controllers\Api\Simrs\Laporan\LapoperasiController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan'
], function () {
   Route::get('/laporanoperasirr',[LapoperasiController::class, 'lapoperasirr'] );
});
