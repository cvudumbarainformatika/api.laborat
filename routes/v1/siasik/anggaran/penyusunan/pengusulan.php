<?php


use App\Http\Controllers\Api\Siasik\Anggaran\PenyusunanAnggaran\PengusulanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'anggaran/penyusunan/pengusulan'
], function () {
    Route::get('/select', [PengusulanController::class, 'selectKegiatan']);
    Route::get('/selectsatuan', [PengusulanController::class, 'selectSatuan']);
    Route::get('/selectitem', [PengusulanController::class, 'selectItem']);
    Route::get('/index', [PengusulanController::class, 'index']);
    Route::post('/save', [PengusulanController::class, 'save']);
    Route::post('/deleterinci', [PengusulanController::class, 'deleterinci']);
    Route::post('/kunci', [PengusulanController::class, 'kunci']);

});
