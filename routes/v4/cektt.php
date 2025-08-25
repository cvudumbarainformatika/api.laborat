<?php

use App\Http\Controllers\Api\v4\TtController;
use Illuminate\Support\Facades\Route;



Route::group([
    // 'middleware' => 'auth:api',
    // 'middleware' => 'jkn.auth',
    'prefix' => 'cektt'
], function () {
    Route::get('/ranap', [TtController::class, 'index']);
});
