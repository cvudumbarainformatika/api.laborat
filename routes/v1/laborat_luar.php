<?php

// use App\Http\Controllers\api\penunjang\TransaksiLaboratController;
use App\Http\Controllers\Api\penunjang\TransaksiLaboratLuarController;
use Illuminate\Support\Facades\Route;


// Route::get('/test', [AuthController::class, 'test']);

Route::middleware('auth:api')
->group(function () {
    Route::get('/transaksi_laborat_luar', [TransaksiLaboratLuarController::class, 'index']);
    Route::get('/transaksi_laborats_luar_details', [TransaksiLaboratLuarController::class, 'get_details']);
});


