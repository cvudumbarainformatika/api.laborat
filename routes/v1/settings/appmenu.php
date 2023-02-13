<?php

use App\Http\Controllers\Api\settings\MenuController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'settings/appmenu'
], function () {
    Route::get('/aplikasi', [MenuController::class, 'aplikasi']);
});
