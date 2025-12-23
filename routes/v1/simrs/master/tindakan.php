<?php

use App\Http\Controllers\Api\Simrs\Master\TindakanController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/listtindakan', [TindakanController::class, 'listtindakan']);
    // Route::post('/simpanmastertindakan', [TindakanController::class, 'simpanmastertindakan']); // ini langsung ke tabel rs30
    Route::post('/simpanmastertindakan', [TindakanController::class, 'simpanTindakanKeTabelSementara']); // diputer dulu ke tabel sementara 
    Route::post('/hapusmastertindakan', [TindakanController::class, 'hidden']);
    Route::post('/tampilkanmastertindakan', [TindakanController::class, 'showAgain']);
});
