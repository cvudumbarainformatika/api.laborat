<?php

use App\Http\Controllers\Api\v1\AppController;
use Illuminate\Support\Facades\Route;



Route::get('/header', [AppController::class, 'header']);

Route::middleware('auth:api')
->group(function () {
    Route::post('/header/store_logo', [AppController::class, 'store_logo']);
    Route::post('/header/store_banner', [AppController::class, 'store_banner']);
    Route::post('/header/store', [AppController::class, 'store']);
    Route::post('/app/store_image_section_one', [AppController::class, 'store_image_section_one']);
    Route::post('/app/store_section_two', [AppController::class, 'store_section_two']);
    Route::post('/app/store_themes', [AppController::class, 'store_themes']);
    Route::post('/app/store_staf', [AppController::class, 'store_staf']);
    Route::post('/app/remove_staf', [AppController::class, 'remove_staf']);
});


