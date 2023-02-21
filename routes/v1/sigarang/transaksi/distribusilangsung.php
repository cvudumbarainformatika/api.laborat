<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\DistribusiLangsungController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi/distribusilangsung'
], function () {
    Route::get('/get-stok-depo', [DistribusiLangsungController::class, 'getStokDepo']);
});
