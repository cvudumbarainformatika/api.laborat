<?php

use App\Http\Controllers\api\penunjang\TransaksiLaboratController;
use Illuminate\Support\Facades\Route;


// Route::get('/test', [AuthController::class, 'test']);

Route::middleware('auth:api')
->group(function () {
    Route::get('/transaksi_laborats', [TransaksiLaboratController::class, 'index']);
    Route::get('/transaksi_laborats/total', [TransaksiLaboratController::class, 'totalData']);
    Route::get('/transaksi_laborats_details', [TransaksiLaboratController::class, 'get_details']);
});


