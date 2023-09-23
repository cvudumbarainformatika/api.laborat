<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Laborat\LaboratController;
use App\Http\Controllers\Api\Simrs\Penunjang\Radiologi\RadiologimetaController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/laborat'
], function () {
    Route::get('/dialoglaboratpoli', [LaboratController::class, 'listmasterpemeriksaanpoli']);
    Route::get('/getnota', [LaboratController::class, 'getnota']);
    Route::post('/simpanpermintaanlaborat', [LaboratController::class, 'simpanpermintaanlaborat']);
<<<<<<< HEAD
    Route::post('/hapuspermintaanlaborat', [LaboratController::class, 'hapuspermintaanlaborat']);
=======

    Route::get('/listmasterpemeriksaanradiologi', [RadiologimetaController::class, 'listmasterpemeriksaanradiologi']);
>>>>>>> 6e2b30650f16fb3860c6ad5ac4241e045e9833a7
});
