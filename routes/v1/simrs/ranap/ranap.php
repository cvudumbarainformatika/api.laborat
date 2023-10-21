<?php

use App\Http\Controllers\Api\Simrs\Ranap\RuanganController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/ruangan'
], function () {
    Route::get('/listruanganranap', [RuanganController::class, 'listruanganranap']);
});
