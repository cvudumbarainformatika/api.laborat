<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Kartustok\KartustokController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\PenyesuaianController;
use Illuminate\Support\Facades\Route;

Route::group(
    [
        'middleware' => 'auth:api',
        'prefix' => 'simrs/farmasinew/penyesuaian'
    ],
    function () {
        // Route::get('/get-obat', [PenyesuaianController::class, 'getObat
        Route::get('/get-obat', [KartustokController::class, 'index']);
        Route::get('/transaksi', [PenyesuaianController::class, 'getTransaksi']);
    }
);