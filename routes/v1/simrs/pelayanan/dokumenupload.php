<?php

use App\Http\Controllers\Api\Simrs\Pelayanan\DokumenUpload\DokumenUploadController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan/dokumenupload'
], function () {
    Route::get('/master', [DokumenUploadController::class, 'master']);
    Route::post('/store', [DokumenUploadController::class, 'store']);
    Route::post('/deletedata', [DokumenUploadController::class, 'deletedata']);
});
