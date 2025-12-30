<?php


use App\Http\Controllers\Api\Siasik\Anggaran\PenyusunanAnggaran\PengusulanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'anggaran/penyusunan/pengusulan'
], function () {
    Route::get('/select', [PengusulanController::class, 'selectKegiatan']);
    Route::get('/index', [PengusulanController::class, 'index']);

});
