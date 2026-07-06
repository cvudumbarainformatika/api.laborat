<?php

use App\Http\Controllers\Api\Siasik\Anggaran\RBAPerubahan\PerubahanPendapatanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'anggaran/perubahan/pendapatan'
], function () {
    Route::get('/index', [PerubahanPendapatanController::class, 'index']);
    Route::post('/save', [PerubahanPendapatanController::class, 'save']);
    Route::post('/penetapan', [PerubahanPendapatanController::class, 'penetapan']);
    Route::post('/delete', [PerubahanPendapatanController::class, 'delete']);
    Route::post('/kunci', [PerubahanPendapatanController::class, 'kunci']);

});
