<?php


use App\Http\Controllers\Api\Siasik\Anggaran\PenyusunanAnggaran\PengusulanController;
use App\Http\Controllers\Api\Siasik\Anggaran\PenyusunanAnggaran\PenyesuaianPrioritasController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'anggaran/penyusunan/prioritas'
], function () {
    Route::get('/select', [PenyesuaianPrioritasController::class, 'selectPengusulan']);
    Route::get('/getrekening', [PenyesuaianPrioritasController::class, 'getRekening']);
    Route::get('/index', [PenyesuaianPrioritasController::class, 'index']);
    Route::post('/save', [PenyesuaianPrioritasController::class, 'save']);
    Route::post('/deleterinci', [PenyesuaianPrioritasController::class, 'deleterinci']);
    Route::post('/kunci', [PenyesuaianPrioritasController::class, 'kunci']);
    Route::post('/updatedata', [PenyesuaianPrioritasController::class, 'updateData']);
    Route::get('/cetakdata', [PenyesuaianPrioritasController::class, 'cetakData']);

});
