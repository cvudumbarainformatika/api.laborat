<?php

use App\Http\Controllers\Api\Siasik\Master\AkunlakController;
use Illuminate\Support\Facades\Route;
Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'master/akunlak'
], function () {
    Route::get('/index', [AkunlakController::class, 'index']);
    Route::get('/select', [AkunlakController::class, 'select']);
    Route::post('/simpan', [AkunlakController::class, 'store']);
    Route::post('/delete', [AkunlakController::class, 'delete']);

});
