<?php

use App\Http\Controllers\Api\Simrs\Pendaftaran\GeneralconsentController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran'
], function () {
    Route::post('getkarcispoli', [GeneralconsentController::class, 'simpangeneralcontent']);
});
