<?php

use App\Http\Controllers\Api\Siasik\Anggaran\RBAPerubahan_pergeseran\PergeseranPerubahanAnggaranController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'anggaran/pergeseranperubahan/anggaran'
], function () {
    Route::get('/index', [PergeseranPerubahanAnggaranController::class, 'index']);
    Route::post('/save', [PergeseranPerubahanAnggaranController::class, 'save']);
    Route::post('/verifikasi', [PergeseranPerubahanAnggaranController::class, 'verifikasi']);
    Route::get('/cetak', [PergeseranPerubahanAnggaranController::class, 'cetakData']);

    Route::post('/simpanbatasan', [PergeseranPerubahanAnggaranController::class, 'simpanBatasan']);
    Route::get('/getbatasan', [PergeseranPerubahanAnggaranController::class, 'getBatasan']);


});