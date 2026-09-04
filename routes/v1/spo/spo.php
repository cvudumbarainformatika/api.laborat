<?php

use App\Http\Controllers\Api\Spo\SpoController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'spo/spo'
], function () {
    Route::get('/getsoplist', [SpoController::class, 'getlistspo']);
    Route::get('/units', [SpoController::class, 'units']);
    Route::get('/form/{id?}', [SpoController::class, 'form']);
    Route::post('/form', [SpoController::class, 'save']);
    Route::delete('/form/{id}', [SpoController::class, 'destroy']);
});
