<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\StokOpnameController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi/opname'
], function () {
    Route::get('/gudangdepo', [StokOpnameController::class, 'getDataGudangDepo']);
    Route::post('/ambil', [ReturController::class, 'index']);
});
