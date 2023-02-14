<?php

use App\Http\Controllers\Api\Dashboardexecutive\KeuanganController;
use App\Http\Controllers\Api\settings\MenuController;
use Illuminate\Support\Facades\Route;



Route::group([
    // 'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'dashboardexecutive'
], function () {
    Route::get('/pendapatan', [KeuanganController::class, 'pendapatan']);
});
