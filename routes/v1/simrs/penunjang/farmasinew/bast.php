<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Bast\BastController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/bast'
], function () {
    Route::get('/dialogsp', [BastController::class, 'dialogsp']);
    Route::get('/dialogpenerimaan', [BastController::class, 'dialogpenerimaan']);
});
