<?php

use App\Http\Controllers\Api\Siasik\Anggaran\RBAPerubahan\PerubahanPaguController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'anggaran/perubahan/pagu'
], function () {
    Route::get('/index', [PerubahanPaguController::class, 'index']);
    Route::post('/save', [PerubahanPaguController::class, 'save']);
    Route::post('/penetapan', [PerubahanPaguController::class, 'penetapan']);
    Route::post('/delete', [PerubahanPaguController::class, 'delete']);
    Route::post('/kunci', [PerubahanPaguController::class, 'kunci']);

});
