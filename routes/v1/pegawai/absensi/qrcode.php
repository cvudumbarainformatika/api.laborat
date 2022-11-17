<?php

use App\Http\Controllers\Api\Pegawai\Master\QrcodeController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'pegawai/absensi/qr'
], function () {
    Route::get('/get-qr', [QrcodeController::class, 'getQr']);
    Route::post('/store', [QrcodeController::class, 'createQr']);
    Route::post('/scan', [QrcodeController::class, 'qrScanned']);
});
