<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Laborat\LaboratController;
use App\Http\Controllers\Api\Simrs\Penunjang\Radiologi\RadiologimetaController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/radiologi'
], function () {
    Route::get('/listmasterpemeriksaanradiologi', [RadiologimetaController::class, 'listmasterpemeriksaanradiologi']);
    Route::get('/jenispermintaanradiologi', [RadiologimetaController::class, 'jenispermintaanradiologi']);
    Route::get('/listpermintaanradiologirinci', [RadiologimetaController::class, 'listpermintaanradiologirinci']);
});
