<?php

use App\Http\Controllers\Api\Pegawai\Master\CutiController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'data'
], function () {
    Route::get('/pegawai', [CutiController::class, 'pegawai']);
});
