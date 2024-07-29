<?php

use App\Http\Controllers\Api\Siasik\TransaksiLS\KontrakController;
use Illuminate\Support\Facades\Route;
Route::group([
    // 'middleware' => 'auth:api',
    'prefix' => 'transaksi/kontrak'
], function () {

    Route::get('/listkontrakcc', [KontrakController::class, 'listkontrakcc']);
    Route::post('/simpankontrakxx', [KontrakController::class, 'simpankontrak']);
});
