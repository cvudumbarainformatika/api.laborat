<?php

use App\Http\Controllers\Api\Mobile\Simrs\Pelayanan\Poli\UploadController;
use Illuminate\Support\Facades\Route;


Route::group([
    // 'middleware' => 'auth:api',
    'middleware' => 'jwt.verify',
    'prefix' => 'simrs/layananpoli/upload'
], function () {
    Route::post('/dokumen', [UploadController::class, 'store']);
    Route::get('/dokumenBy', [UploadController::class, 'dokumenBy']);
    Route::get('/master', [UploadController::class, 'master']);
});
