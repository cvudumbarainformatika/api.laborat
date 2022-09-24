<?php

use App\Http\Controllers\Api\v1\BukutamuController;
use Illuminate\Support\Facades\Route;



Route::post('/bukutamu/store', [BukutamuController::class, 'store'])->middleware('throttle:2, 1');;

Route::middleware('auth:api')
->group(function () {
    // Route::get('/galleries', [GalleryController::class, 'index']);
    // Route::post('/gallery/upload', [GalleryController::class, 'upload']);
});


