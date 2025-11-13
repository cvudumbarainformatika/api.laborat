<?php

use App\Http\Controllers\Api\Simrs\HomeCare\DiagnosaHcController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/home-care/diagnosa'
], function () {
    Route::post('/hapusdiagnosa', [DiagnosaHcController::class, 'hapusdiagnosa']);
    Route::post('/simpandiagnosa', [DiagnosaHcController::class, 'simpandiagnosa']);
    Route::get('/listdiagnosa', [DiagnosaHcController::class, 'listdiagnosa']);
});
