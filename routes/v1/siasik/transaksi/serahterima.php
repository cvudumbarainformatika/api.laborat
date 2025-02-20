<?php

use App\Http\Controllers\Api\Siasik\TransaksiLS\SerahterimaController;
use Illuminate\Support\Facades\Route;
Route::group([
    // 'middleware' => 'auth:api',
    'prefix' => 'transaksi/serahterima'
], function () {

    Route::get('/getkontrak', [SerahterimaController::class, 'getkontrak']);
    Route::post('/savedata', [SerahterimaController::class, 'savedata']);

});
