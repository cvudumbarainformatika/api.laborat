<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\PenjualanBebas\PenjualanBebasController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/penjualanbebas'
], function () {
    Route::get('/pihak-tiga', [PenjualanBebasController::class, 'getPihakTiga']);
    
});

