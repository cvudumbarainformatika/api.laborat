<?php

// use App\Http\Controllers\Api\v1\BeritaController;
// use App\Http\Controllers\Api\v1\ScrapperController;
// use App\Http\Controllers\Api\v1\UploadWord;

use App\Http\Controllers\Api\v1\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')
->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/user/store', [UserController::class, 'store']);
    Route::post('/user/destroy', [UserController::class, 'destroy']);
});


