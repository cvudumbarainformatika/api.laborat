<?php

use App\Http\Controllers\Api\Mobile\Simrs\Kunjungan\KunjunganPasienController;
use Illuminate\Support\Facades\Route;


Route::group([
    // 'middleware' => 'auth:api',
    'middleware' => 'jwt.verify',
    'prefix' => 'simrs/kunjungan'
], function () {
    Route::get('/pasien', [KunjunganPasienController::class, 'pasien']);
});
