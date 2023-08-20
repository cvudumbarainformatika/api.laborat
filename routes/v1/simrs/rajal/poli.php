<?php

use App\Http\Controllers\Api\Simrs\Rajal\PoliController as RajalPoliController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/rajal/poli'
], function () {
    // Route::get('/listminmaxobat', [MinmaxobatController::class, 'listminmaxobat']);
    Route::get('/kunjunganpoli', [RajalPoliController::class, 'kunjunganpoli']);
    Route::post('/save-pemeriksaanfisik', [RajalPoliController::class, 'save_pemeriksaanfisik']);
});
