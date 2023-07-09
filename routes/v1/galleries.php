<?php

// use App\Http\Controllers\Api\penunjang\InterpretasiController;

use App\Http\Controllers\Api\Simrs\Gallery\GalleryController;
use Illuminate\Support\Facades\Route;


// Route::get('/test', [AuthController::class, 'test']);

Route::middleware('auth:api')
    ->group(function () {
        Route::post('/galleries/data', [GalleryController::class, 'index']);
    });
