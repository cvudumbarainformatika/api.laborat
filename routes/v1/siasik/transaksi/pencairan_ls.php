<?php

use App\Http\Controllers\Api\Siasik\TransaksiLS\NPK_LSController;
use App\Http\Controllers\Api\Siasik\TransaksiLS\Pencairan_LSController;
use Illuminate\Support\Facades\Route;
Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'transaksi/pencairanls'
], function () {
    Route::get('/listdata', [Pencairan_LSController::class, 'listdata']);
    Route::post('/pencairan', [Pencairan_LSController::class, 'pencairan']);
});
