<?php

use App\Http\Controllers\Api\Simrs\Pendaftaran\Ranap\SepranapController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran'
], function () {
    Route::get('sepranapbynoka', [SepranapController::class, 'sepranap']);
});
