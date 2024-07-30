<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\SetNewStokController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/stok'
], function () {
    Route::get('/new-stok', [SetNewStokController::class, 'setNewStok']);
    Route::get('/cek-harga', [SetNewStokController::class, 'cekHargaGud']);
    Route::get('/isi-harga', [SetNewStokController::class, 'insertHarga']);
    Route::get('/new-stok-opname', [SetNewStokController::class, 'setStokOpnameAwal']);
    Route::post('/perbaikan-stok', [SetNewStokController::class, 'newPerbaikanStok']);
    Route::post('/perbaikan-stok-per-depo', [SetNewStokController::class, 'PerbaikanStokPerDepo']);
    Route::post('/cek-penerimaan', [SetNewStokController::class, 'cekPenerimaan']);
});
