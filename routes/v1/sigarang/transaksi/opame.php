<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\StokOpnameController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi/opname'
], function () {
    Route::get('/gudangdepo', [StokOpnameController::class, 'getDataGudangDepo']);
    Route::post('/ambil', [StokOpnameController::class, 'index']);
    Route::get('/monthly-stok', [StokOpnameController::class, 'getDataStokOpname']);
    Route::get('/store-opname', [StokOpnameController::class, 'storeMonthly']);
    Route::get('/opname-by-depo', [StokOpnameController::class, 'getDataStokOpnameByDepo']);
    Route::post('/simpan-penyesuaian', [StokOpnameController::class, 'storePenyesuaian']);
});
