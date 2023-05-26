<?php

use App\Http\Controllers\Api\Antrean\master\DisplayController;
use Illuminate\Support\Facades\Route;



Route::group([
    // 'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'fordisplay'
], function () {
    Route::get('/display', [DisplayController::class, 'display']);
});
