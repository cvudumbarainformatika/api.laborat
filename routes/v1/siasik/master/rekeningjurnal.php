<?php

use App\Http\Controllers\Api\Siasik\Master\RekeningJurnalController;
use Illuminate\Support\Facades\Route;
Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'master/rekening'
], function () {
    Route::get('/getrekening', [RekeningJurnalController::class, 'getRekening']);
    Route::get('/index', [RekeningJurnalController::class, 'index']);
    Route::post('/simpan', [RekeningJurnalController::class, 'store']);

});
