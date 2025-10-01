<?php

use App\Http\Controllers\Api\Simrs\Master\KamarController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/kamar', [KamarController::class, 'listKamar']);
    Route::get('/listviewkamar', [KamarController::class, 'showKamar']);
    Route::get('/showkamar', [KamarController::class, 'showKamar2']);
    Route::get('/showBed', [KamarController::class, 'showBed']);
});
