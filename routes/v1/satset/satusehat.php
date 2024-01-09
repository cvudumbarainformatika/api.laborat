<?php

// use App\Http\Controllers\Api\Satusehat\OrganizationController;

use App\Http\Controllers\Api\Satusehat\AuthController;
use App\Http\Controllers\Api\Satusehat\LocationController;
use App\Http\Controllers\Api\Satusehat\OrganizationController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'satusehat'
], function () {
    Route::get('/authorization', [AuthController::class, 'index']);
    Route::get('/listOrganisasiRs', [OrganizationController::class, 'listOrganisasiRs']);
    Route::post('/postOrganisasiRs', [OrganizationController::class, 'postOrganisasiRs']);
    Route::get('/organization', [OrganizationController::class, 'cariorganisasidisatset']);
    Route::get('/sendToSatset', [OrganizationController::class, 'sendToSatset']);


    Route::get('/listRuanganRajal', [LocationController::class, 'listRuanganRajal']);
});
