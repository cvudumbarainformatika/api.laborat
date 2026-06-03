<?php

use App\Http\Controllers\Api\Simrs\Radiologi\RadiologiController;
use Illuminate\Support\Facades\Route;


// Route::get('/test', [AuthController::class, 'test']);



Route::middleware('api.orthanc')
  ->group(function () {
    Route::post('/radiologi/callback', [RadiologiController::class, 'handleWebhook']);
  });

// Route::post('/post_from_lis', [LisController::class, 'store']);
