<?php

use App\Http\Controllers\Api\Simrs\Kasir\BillingbynoregController;
use App\Http\Controllers\Api\Simrs\Kasir\KasirrajalController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/kasir'
], function () {
    Route::get('/rajal/kunjunganpoli', [KasirrajalController::class, 'kunjunganpoli']);
    Route::get('/rajal/billbynoreg', [BillingbynoregController::class, 'billbynoregrajal']);

    Route::get('/rajal/tagihanpergolongan', [KasirrajalController::class, 'tagihanpergolongan']);
    Route::post('/rajal/pembayaran', [KasirrajalController::class, 'pembayaran']);
});
