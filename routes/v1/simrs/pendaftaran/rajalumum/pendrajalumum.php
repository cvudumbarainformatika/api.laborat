<?php

use App\Http\Controllers\Api\Simrs\Historypasien\HistorypasienController;
use App\Http\Controllers\Api\Simrs\Master\PasienController;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajalumum\DaftarrajalumumController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran'
], function () {

    Route::post('/rajalumumsimpan', [DaftarrajalumumController::class, 'simpandaftar']);
    Route::post('/createsep', [DaftarrajalumumController::class, 'createsep']);
    // Route::post('/rajalbpjssimpan', [DaftarrajalbpjsController::class, 'simpandaftarbpjs']);
    Route::get('/listpasienumum', [DaftarrajalumumController::class, 'listpasienumum']);
    Route::get('/masterpasien', [PasienController::class,'index']);
    Route::get('/historypasien', [HistorypasienController::class, 'historykunjunganpasien']);
});
