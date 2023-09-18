<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Laborat\LaboratController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/laborat'
], function () {
    Route::get('/dialoglaboratpoli', [LaboratController::class, 'listmasterpemeriksaanpoli']);
    Route::get('/simpanpermintaanlaborat', [LaboratController::class, 'simpanpermintaanlaborat']);
});
