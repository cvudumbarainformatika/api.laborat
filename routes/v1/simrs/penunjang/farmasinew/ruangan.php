<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Ruangan\PemakaianRuanganController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/penunjang/farmasinew/ruangan'
], function () {
    Route::get('/get-stok-ruangan', [PemakaianRuanganController::class, 'getStokRuangan']);
    Route::post('/simpan', [PemakaianRuanganController::class, 'simpanpemaikaianruangan']);
    Route::post('/selesai', [PemakaianRuanganController::class, 'selesaiPakai']);
});
