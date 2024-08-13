<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\PenjualanBebas\PenjualanBebasController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/penjualanbebas'
], function () {
    Route::get('/pihak-tiga', [PenjualanBebasController::class, 'getPihakTiga']);
    Route::get('/cari-obat', [PenjualanBebasController::class, 'pencarianObat']);
    Route::post('/simpan', [PenjualanBebasController::class, 'simpan']);
    
});
