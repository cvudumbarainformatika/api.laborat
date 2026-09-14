<?php

use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\BillingController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/ranap/layanan/billing'
], function () {
    Route::get('/rekap-billing', [BillingController::class, 'getRekapBilling']);
    Route::get('/faktur-detail', [BillingController::class, 'getFakturDetail']);
});
