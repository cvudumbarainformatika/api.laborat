<?php

use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Persediaan\PersediaanFiFoController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/farmasi/pemakaian'
], function () {
    Route::get('/get-persediaan', [PersediaanFiFoController::class, 'getPersediaan']);
    Route::get('/get-mutasi', [PersediaanFiFoController::class, 'getMutasi']);
});
