<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasi\MinmaxobatController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/maping'
], function () {
    Route::get('/listminmaxobat', [MinmaxobatController::class, 'listminmaxobat']);
    Route::get('/carilistminmaxbyobat', [MinmaxobatController::class, 'caribynamaobat']);
    Route::post('/minmaxobat', [MinmaxobatController::class, 'simpan']);
});
