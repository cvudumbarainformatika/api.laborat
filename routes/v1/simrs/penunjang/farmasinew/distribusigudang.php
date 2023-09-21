<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang\DistribusigudangController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/gudang/distribusi'
], function () {
    Route::get('/listpermintaandepo', [DistribusigudangController::class, 'listpermintaandepo']);
    Route::post('/verifpermintaanobat', [DistribusigudangController::class, 'verifpermintaanobat']);
    Route::get('/rencanadistribusikedepo', [DistribusigudangController::class, 'rencanadistribusikedepo']);
});
