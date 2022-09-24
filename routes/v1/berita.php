<?php

use App\Http\Controllers\Api\v1\BeritaController;
use App\Http\Controllers\Api\v1\ScrapperController;
use App\Http\Controllers\Api\v1\UploadWord;
use Illuminate\Support\Facades\Route;



Route::get('/berita/data_beranda', [BeritaController::class, 'web_beranda']);
Route::get('/berita/web_popular', [BeritaController::class, 'web_popular']);
Route::get('/berita/web_content', [BeritaController::class, 'web_content']);
Route::get('/berita/kota', [ScrapperController::class, 'index']);

Route::middleware('auth:api')
->group(function () {
    Route::get('/beritas', [BeritaController::class, 'index']);
    Route::post('/berita/upload_word', [UploadWord::class, 'upload_word']);
    Route::post('/berita/store', [BeritaController::class, 'store']);
    Route::post('/berita/update_status', [BeritaController::class, 'update_status']);
    Route::post('/berita/destroy', [BeritaController::class, 'destroy']);
});


