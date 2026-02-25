<?php

use App\Http\Controllers\Api\Siasik\Anggaran\Pergeseran\PergeseranPaguController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'anggaran/pergeseran/pagu'
], function () {
    Route::get('/index', [PergeseranPaguController::class, 'index']);
    Route::post('/save', [PergeseranPaguController::class, 'save']);
    Route::post('/delete', [PergeseranPaguController::class, 'delete']);
    Route::post('/kunci', [PergeseranPaguController::class, 'kunci']);

});
