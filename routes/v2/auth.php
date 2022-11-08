<?php

use App\Http\Controllers\Api\Mobile\Auth\AuthController;
use Illuminate\Support\Facades\Route;



Route::post('/login', [AuthController::class, 'login']);
