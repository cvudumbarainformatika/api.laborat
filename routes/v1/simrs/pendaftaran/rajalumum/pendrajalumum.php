<?php

use App\Http\Controllers\Api\Simrs\Historypasien\HistorypasienController;
use App\Http\Controllers\Api\Simrs\Master\PasienController;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal\DaftarrajalController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran'
], function () {

    //simpan rs17  ==> rajalumumsimpan
    Route::post('/simpandaftar', [DaftarrajalController::class, 'simpandaftar']);
    Route::get('/masterpasien', [PasienController::class,'listpasien']);
    Route::get('/historypasien', [HistorypasienController::class, 'historykunjunganpasien']);

    Route::get('/kunjunganpasienbpjs', [DaftarrajalController::class, 'daftarkunjunganpasienbpjs']);



});
