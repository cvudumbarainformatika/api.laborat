<?php

use App\Http\Controllers\Api\Simrs\Laporan\Keuangan\RincianPerunitController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/keuangan'
], function () {
    Route::get('/rincianperunit', [RincianPerunitController::class, 'rincianperunit']);
});
