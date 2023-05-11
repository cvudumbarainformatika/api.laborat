<?php

use App\Http\Controllers\Api\settings\AksesUserController;
use Illuminate\Support\Facades\Route;

Route::group(
    [
        'middleware' => 'auth:api',
        // 'middleware' => 'jwt.verify',
        'prefix' => 'settings/appakses'
    ],
    function () {
        Route::get('/akses', [AksesUserController::class, 'userAkses']);
        Route::post('/store-akses', [AksesUserController::class, 'storeAkses']);
    }
);
