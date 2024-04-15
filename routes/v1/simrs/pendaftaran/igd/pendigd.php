<?php

use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal\DaftarigdController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran/igd'
], function () {
    Route::get('/daftarkunjunganpasienbpjs', [DaftarigdController::class, 'daftarkunjunganpasienbpjs']);
    Route::post('/simpankunjunganigd', [DaftarigdController::class, 'simpankunjunganigd']);
});
