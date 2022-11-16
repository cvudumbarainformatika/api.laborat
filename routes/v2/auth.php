<?php

use App\Http\Controllers\Api\Mobile\Auth\AuthController;
use Illuminate\Support\Facades\Route;



Route::post('/login', [AuthController::class, 'login']);
Route::post('/reset-device', [AuthController::class, 'resetDevice']);
Route::group([
    // 'middleware' => 'auth:api',
    'middleware' => 'jwt.verify',
    'prefix' => 'user'
], function () {
    Route::get('/me', [AuthController::class, 'me']);
});
