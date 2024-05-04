<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Penerimaan\ListstokgudangController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\StokrealController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/penerimaan'
], function () {
    Route::get('/listepenerimaan', [PenerimaanController::class, 'listepenerimaan']);
    Route::get('/dialogpemesananobat', [PenerimaanController::class, 'listpemesananfix']);
    Route::get('/stokgudang', [ListstokgudangController::class, 'stokgudang']);
    Route::post('/simpan', [PenerimaanController::class, 'simpanpenerimaan']);
    Route::post('/kuncipenerimaan', [PenerimaanController::class, 'kuncipenerimaan']);

    Route::post('/simpanpenerimaanlangsung', [PenerimaanController::class, 'simpanpenerimaanlangsung']);

    Route::post('/batal-header', [PenerimaanController::class, 'batalHeader']);
    Route::post('/batal-rinci', [PenerimaanController::class, 'batalRinci']);

    Route::post('/insertsementara', [StokrealController::class, 'insertsementara']);
    Route::post('/updatestoksementara', [StokrealController::class, 'updatestoksementara']);

    Route::get('/liststokreal', [StokrealController::class, 'liststokreal']); // ini list stok opname

    Route::get('/list-stok-sekarang', [StokrealController::class, 'listStokSekarang']);
    Route::get('/obat-mau-disesuaikan', [StokrealController::class, 'obatMauDisesuaikan']);
    Route::post('/update-stok-sekarang', [StokrealController::class, 'updatehargastok']);
    Route::get('/data-alokasi', [StokrealController::class, 'dataAlokasi']);
});
