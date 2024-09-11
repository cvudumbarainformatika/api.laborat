<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Cathlab\ReqCatlabController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/cathlab/'
], function (){
    Route::get('reqcathlab',[ReqCatlabController::class, 'reqcathlab']);
    Route::post('terimapasien',[ReqCatlabController::class, 'terimapasien']);
});
