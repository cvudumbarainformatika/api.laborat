<?php

use App\Http\Controllers\Api\Siasik\TransaksiLS\NPK_LSController;
use Illuminate\Support\Facades\Route;
Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'transaksi/npkls'
], function () {
    Route::get('/listdata', [NPK_LSController::class, 'listdata']);
    Route::get('/selectnpd', [NPK_LSController::class, 'selectNpd']);
    Route::get('/getrincian', [NPK_LSController::class, 'getlistrinci']);
    Route::post('/savedata', [NPK_LSController::class, 'savedata']);
    Route::post('/deleterinci', [NPK_LSController::class, 'deleterinci']);
    Route::post('/kuncidata', [NPK_LSController::class, 'kuncidata']);
});
