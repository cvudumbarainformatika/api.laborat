<?php

use App\Http\Controllers\Api\Simrs\Igd\VisumController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/igd/visum',
], function () {
    Route::get('/list', [VisumController::class, 'list']);
    Route::post('/simpan', [VisumController::class, 'simpan']);
    Route::post('/hapus', [VisumController::class, 'hapus']);
});
