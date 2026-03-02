<?php

use App\Http\Controllers\Api\Siasik\Anggaran\Pergeseran\PergeseranAnggaranController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'anggaran/pergeseran/rincian'
], function () {
    // Route::get('/select', [PengusulanController::class, 'selectKegiatan']);
    // Route::get('/selectsatuan', [PengusulanController::class, 'selectSatuan']);
    // Route::get('/selectitem', [PengusulanController::class, 'selectItem']);
    Route::get('/index', [PergeseranAnggaranController::class, 'index']);
    Route::post('/save', [PergeseranAnggaranController::class, 'save']);
    Route::post('/deleterinci', [PergeseranAnggaranController::class, 'deleterinci']);
    Route::post('/kunci', [PergeseranAnggaranController::class, 'kunci']);

});