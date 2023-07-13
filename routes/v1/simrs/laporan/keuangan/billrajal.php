<?php

use App\Http\Controllers\Api\Simrs\Laporan\Keuangan\AllbillrajalController;
use App\Http\Controllers\Api\Simrs\Laporan\Keuangan\AllbillrajalperpoliController;
use App\Http\Controllers\Api\Simrs\Laporan\Keuangan\AllbillranapController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan'
], function () {
    //RAJAL
    Route::get('/laporanallbillrajal', [AllbillrajalController::class, 'kumpulanbillpasien']);
    Route::get('/allbillperlopi', [AllbillrajalperpoliController::class, 'allbillperlopi']);
    Route::get('/billpoli', [AllbillrajalperpoliController::class, 'billpoli']);

    //RANAP
    Route::get('/allbillranap',[AllbillranapController::class, 'allbillranap']);
});
