<?php

use App\Http\Controllers\Api\Satusehat\Bundle\KunjunganController;
use Illuminate\Support\Facades\Route;



Route::group([
    // 'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'satusehat/bundle'
], function () {
    Route::get('/kirim-kunjungan', [KunjunganController::class, 'index']);
});