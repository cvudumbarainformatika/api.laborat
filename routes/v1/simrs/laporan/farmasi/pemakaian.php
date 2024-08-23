<?php

use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Pemakaian\PemakaianObatController;
use Illuminate\Support\Facades\Route;
Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/farmasi/pemakaian'
], function () {
    Route::get('/get-pemakaian', [PemakaianObatController::class, 'getPemakaianObat']);
});