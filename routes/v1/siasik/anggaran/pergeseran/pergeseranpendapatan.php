<?php

use App\Http\Controllers\Api\Siasik\Anggaran\Pergeseran\PergeseranPendapatanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'anggaran/pergeseran/pendapatan'
], function () {
    Route::get('/index', [PergeseranPendapatanController::class, 'index']);
    Route::post('/save', [PergeseranPendapatanController::class, 'save']);
    Route::get('/getrekening', [PergeseranPendapatanController::class, 'getRekening']);
    Route::post('/delete', [PergeseranPendapatanController::class, 'delete']);
    Route::post('/kunci', [PergeseranPendapatanController::class, 'kunci']);

});
