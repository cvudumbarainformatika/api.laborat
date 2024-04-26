<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang\BarangRusakController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/farmasinew/barangrusak/'
], function () {
    Route::get('obat', [BarangRusakController::class, 'cariObat']);
    Route::get('bacth', [BarangRusakController::class, 'cariBatch']);
    Route::get('penerimaan', [BarangRusakController::class, 'cariPenerimaan']);

    // Route::post('/simpan', [ReturkepbfController::class, 'simpanretur']);
});
