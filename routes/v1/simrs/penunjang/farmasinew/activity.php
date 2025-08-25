<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Activity\LogActivityController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/activity'
], function () {
    Route::get('/list', [LogActivityController::class, 'list']);
});
