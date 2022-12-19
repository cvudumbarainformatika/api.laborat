<?php

use App\Http\Controllers\Api\Mobile\Auth\AuthController;
use Illuminate\Support\Facades\Route;



Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/reset-device', [AuthController::class, 'resetDevice']);

Route::group([
    // 'middleware' => 'auth:api',
    'middleware' => 'jwt.verify',
    'prefix' => 'user'
], function () {
    Route::post('/reset-device', [AuthController::class, 'resetDevice']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/new-password', [AuthController::class, 'newPassword']);
});
