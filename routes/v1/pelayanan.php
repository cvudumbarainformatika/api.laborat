<?php

// use App\Http\Controllers\Api\v1\BeritaController;
// use App\Http\Controllers\Api\v1\ScrapperController;
// use App\Http\Controllers\Api\v1\UploadWord;

use App\Http\Controllers\Api\v1\PelayananController;
use Illuminate\Support\Facades\Route;



// Route::get('/berita/data_beranda', [BeritaController::class, 'web_beranda']);
Route::get('/pelayanan/web_content', [PelayananController::class, 'web_content']);
// Route::get('/berita/kota', [ScrapperController::class, 'index']);

Route::middleware('auth:api')
->group(function () {
    Route::get('/pelayanans', [PelayananController::class, 'index']);
    Route::post('/pelayanan/store', [PelayananController::class, 'store']);
    Route::post('/pelayanan/destroy', [PelayananController::class, 'destroy']);
    // Route::post('/berita/upload_word', [UploadWord::class, 'upload_word']);
    // Route::post('/berita/update_status', [BeritaController::class, 'update_status']);
});


