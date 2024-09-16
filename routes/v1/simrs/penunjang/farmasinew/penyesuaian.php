<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\PenyesuaianController;
use Illuminate\Support\Facades\Route;

Route::group(
    [
        'middleware' => 'auth:api',
        'prefix' => 'simrs/farmasinew/penyesuaian'
    ],
    function () {
        Route::get('/get-obat', [PenyesuaianController::class, 'getObat']);
        Route::get('/transaksi', [PenyesuaianController::class, 'getTransaksi']);
    }
);
