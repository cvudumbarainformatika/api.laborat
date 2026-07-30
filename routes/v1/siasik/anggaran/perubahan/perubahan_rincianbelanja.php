<?php

use App\Http\Controllers\Api\Siasik\Anggaran\RBAPerubahan\PerubahanBelanjaController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'anggaran/perubahan/belanja'
], function () {
    Route::get('/select', [PerubahanBelanjaController::class, 'selectKegiatan']);
    // Route::get('/selectsatuan', [PerubahanBelanjaController::class, 'selectSatuan']);
    Route::get('/selectitemlama', [PerubahanBelanjaController::class, 'selectItemlama']);
    // Route::get('/selectitem', [PerubahanBelanjaController::class, 'selectItem']);
    Route::get('/index', [PerubahanBelanjaController::class, 'index']);
    Route::post('/save', [PerubahanBelanjaController::class, 'save']);
    Route::post('/deleterinci', [PerubahanBelanjaController::class, 'deleterinci']);
    Route::post('/kunci', [PerubahanBelanjaController::class, 'kunci']);

});
