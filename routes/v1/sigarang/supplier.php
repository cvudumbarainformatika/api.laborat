<?php

use App\Http\Controllers\Api\v1\SupplierController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'supplier'
], function () {
    Route::get('/index', [SupplierController::class, 'index']);
});
