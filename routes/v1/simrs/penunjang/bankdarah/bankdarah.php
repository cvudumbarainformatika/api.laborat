<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Bankdarah\BankDarahController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/bankdarah'
], function () {
    Route::get('/getmaster', [BankDarahController::class, 'getmaster']);
    // Route::get('/getnota', [OperasiIrdController::class, 'getnota']);
    // Route::get('/getdata', [OperasiIrdController::class, 'getdata']);
    Route::post('/simpanpermintaan', [BankDarahController::class, 'simpandata']);
    // Route::post('/hapuspermintaan', [OperasiIrdController::class, 'hapusdata']);
});
