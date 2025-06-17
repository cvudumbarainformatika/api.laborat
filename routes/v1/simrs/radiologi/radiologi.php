<?php

use App\Http\Controllers\Api\Simrs\Radiologi\RadiologiController;
use App\Http\Controllers\Api\Simrs\Radiologi\RadiologiLuarController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/radiologi/radiologi'
], function () {
    Route::get('/pasienradiologi', [RadiologiController::class, 'index']); 
    Route::get('/getDataPasienRadiologiByNota', [RadiologiController::class, 'getDataPasienRadiologiByNota']); 
    Route::post('/simpanHasilByKode', [RadiologiController::class, 'simpanHasil']);
    Route::post('/terimapasienradiologi', [RadiologiController::class, 'terimapasienradiologi']); 
    Route::post('/batalkanpasienradiologi', [RadiologiController::class, 'batalkanpasienradiologi']); 
    Route::post('/selesaikanlayananradiologi', [RadiologiController::class, 'selesaikanlayananradiologi']); 


    Route::post('/simpanPermintaan', [RadiologiLuarController::class, 'simpanPermintaan']); 
    Route::get('/pasienradiologiluar', [RadiologiLuarController::class, 'index']); 
    Route::post('/simpanHasilRadiologiLuar', [RadiologiLuarController::class, 'simpanHasilRadiologiLuar']); 
    Route::post('/terimapasienradiologiluar', [RadiologiLuarController::class, 'terimapasienradiologiluar']); 
    Route::post('/batalkanpasienradiologiluar', [RadiologiLuarController::class, 'batalkanpasienradiologiluar']); 
    Route::post('/selesaikanlayananradiologiluar', [RadiologiLuarController::class, 'selesaikanlayananradiologiluar']); 

    
});