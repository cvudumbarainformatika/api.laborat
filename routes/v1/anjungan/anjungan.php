<?php

use App\Http\Controllers\Api\Anjungan\AnjunganController;
use Illuminate\Support\Facades\Route;


Route::group([
    // 'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'anjungan'
], function () {
    Route::get('/cari-rujukan', [AnjunganController::class, 'cari_rujukan']);
    Route::get('/cari-noka', [AnjunganController::class, 'cari_noka']);
});
