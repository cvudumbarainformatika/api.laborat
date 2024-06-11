<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Ambulan\AmbulanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/ambulan/'
], function (){
    Route::get('gettujuanambulan',[AmbulanController::class, 'getTujuanAmbulan']);
});
