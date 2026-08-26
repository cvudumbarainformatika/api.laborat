<?php

use App\Http\Controllers\Api\Siasik\Anggaran\RBAPerubahan_pergeseran\PergeseranPerubahanPaguController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'anggaran/pergeseranperubahan/pagu'
], function () {
    Route::get('/index', [PergeseranPerubahanPaguController::class, 'index']);
    Route::post('/save', [PergeseranPerubahanPaguController::class, 'save']);
    // Route::get('/getrekening', [PergeseranPerubahanPaguController::class, 'getRekening']);
    // Route::post('/delete', [PergeseranPerubahanPaguController::class, 'delete']);
    // Route::post('/kunci', [PergeseranPerubahanPaguController::class, 'kunci']);

});
