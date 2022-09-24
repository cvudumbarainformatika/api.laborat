<?php

use App\Http\Controllers\Api\v1\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')
->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    // Route::post('/gallery/upload', [GalleryController::class, 'upload']);
});


