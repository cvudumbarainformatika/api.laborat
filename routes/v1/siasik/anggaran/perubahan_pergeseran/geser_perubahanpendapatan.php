<?php

use App\Http\Controllers\Api\Siasik\Anggaran\RBAPerubahan_pergeseran\PergeseranPerubahanPendapatanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'anggaran/pergeseranperubahan/pendapatan'
], function () {
    Route::get('/index', [PergeseranPerubahanPendapatanController::class, 'index']);
    Route::post('/save', [PergeseranPerubahanPendapatanController::class, 'save']);
    Route::get('/getrekening', [PergeseranPerubahanPendapatanController::class, 'getRekening']);
    Route::post('/delete', [PergeseranPerubahanPendapatanController::class, 'delete']);
    Route::post('/kunci', [PergeseranPerubahanPendapatanController::class, 'kunci']);

});
