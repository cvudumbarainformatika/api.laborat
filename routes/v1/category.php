<?php

use App\Http\Controllers\Api\v1\CategoryController;
use Illuminate\Support\Facades\Route;



// Route::get('/carousel', [CarouselController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);

Route::middleware('auth:api')
->group(function () {
    Route::post('/store_category', [CategoryController::class, 'store']);
    Route::post('/delete_category', [CategoryController::class, 'destroy']);
});


