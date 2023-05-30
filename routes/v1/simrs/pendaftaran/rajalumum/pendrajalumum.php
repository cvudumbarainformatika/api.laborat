<?php

use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajalumum\DaftarrajalumumController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran'
], function () {

    Route::post('/rajalumumsimpan', [DaftarrajalumumController::class, 'simpandaftar']);
    Route::post('/rajalbpjssimpan', [DaftarrajalbpjsController::class, 'simpandaftarbpjs']);
    Route::get('/listpasienumum', [DaftarrajalumumController::class, 'listpasienumum']);
});
