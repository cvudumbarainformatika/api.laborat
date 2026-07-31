<?php

use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\RekonsiliasiObatController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/ranap/layanan/rekonsiliasiobat'
], function () {
    Route::post('/simpandata', [RekonsiliasiObatController::class, 'simpandata']);
    Route::post('/hapusdata', [RekonsiliasiObatController::class, 'hapusdata']);
    Route::post('/simpanpersetujuan', [RekonsiliasiObatController::class, 'simpanpersetujuan']);
    Route::post('/hapuspersetujuan', [RekonsiliasiObatController::class, 'hapuspersetujuan']);
    Route::get('/list', [RekonsiliasiObatController::class, 'list']);
});
