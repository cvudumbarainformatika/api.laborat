<?php

use App\Http\Controllers\Api\Simrs\Pelayanan\JasaVisiteKonsul\JasaVisiteKonsulController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Tindakan\TindakanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/layanan/jasa'
], function () {
    Route::get('/list', [JasaVisiteKonsulController::class, 'index']); 
    Route::post('/simpan', [JasaVisiteKonsulController::class, 'simpan']); 
    Route::post('/hapus', [JasaVisiteKonsulController::class, 'hapus']); 
    // Route::get('/listtindakanranap', [TindakanController::class, 'getTindakanRanap']); // fixed
});
