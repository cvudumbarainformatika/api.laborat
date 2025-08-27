<?php

use App\Http\Controllers\Api\Simrs\Kasir\BillingbynoregController;
use App\Http\Controllers\Api\Simrs\Kasir\CariKarcisController;
use App\Http\Controllers\Api\Simrs\Kasir\FlagingManualVaController;
use App\Http\Controllers\Api\Simrs\Kasir\KasirrajalController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/kasir'
], function () {
    Route::get('/rajal/cari-karcis', [CariKarcisController::class, 'carikarcis']);
    Route::get('/rajal/cari-obat', [CariKarcisController::class, 'cariobat']);
    Route::get('/rajal/cari-tindakan', [CariKarcisController::class, 'caritindakan']);
    Route::get('/rajal/cari-tindakan-operasi', [CariKarcisController::class, 'caritindakanoperasi']);
    Route::get('/rajal/cari-laborat', [CariKarcisController::class, 'carilaborat']);
    Route::get('/rajal/cari-radiologi', [CariKarcisController::class, 'cariradiologi']);
    Route::get('/rajal/cari-sharingbpjs', [CariKarcisController::class, 'getSharingRajal']);


    Route::get('/rajal/kunjunganpoli', [KasirrajalController::class, 'kunjunganpoli']);
    Route::get('/rajal/billbynoreg', [BillingbynoregController::class, 'billbynoregrajalx']);

    Route::get('/rajal/tagihanpergolongan', [KasirrajalController::class, 'tagihanpergolongan']);
    Route::post('/rajal/pembayarankarcis', [KasirrajalController::class, 'pembayarankarcis']);


    // kasir igd
    Route::get('/igd/billbynoreg', [BillingbynoregController::class, 'billbynoregigd']);

    Route::get('/va/listva', [FlagingManualVaController::class, 'listva']);
    Route::post('/va/flagingmanualva', [FlagingManualVaController::class, 'flagingmanual']);
});
