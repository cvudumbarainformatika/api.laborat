<?php

use App\Http\Controllers\Api\penunjang\TransaksiLaboratController;
use Illuminate\Support\Facades\Route;


// Route::get('/test', [AuthController::class, 'test']);

Route::middleware('auth:api')
->group(function () {
    Route::get('/transaksi_laborats', [TransaksiLaboratController::class, 'index']);
    Route::get('/transaksi_laborats/total', [TransaksiLaboratController::class, 'totalData']);
    Route::get('/transaksi_laborats_details', [TransaksiLaboratController::class, 'get_details']);
    Route::post('/transaksi_laborats/update_complete', [TransaksiLaboratController::class, 'update_complete']);

     // tto lis
     Route::post('/transaksi_laborats_kunci_dan_kirim_ke_lis', [TransaksiLaboratController::class, 'kirim_ke_lis']);
});


