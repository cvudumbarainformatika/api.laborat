<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang\KonsinyasiController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/farmasinew/gudang'
], function () {
    Route::get('/list-pemakaian-konsinyasi', [KonsinyasiController::class, 'getListPemakaianKonsinyasi']);
});
