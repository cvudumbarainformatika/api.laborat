<?php

use App\Http\Controllers\Api\Antrean\master\PoliController;
use App\Http\Controllers\Api\Simrs\Rajal\EditsuratbpjsController;
use App\Http\Controllers\Api\Simrs\Rajal\PoliController as RajalPoliController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/rajal/poli'
], function () {
    // Route::get('/listminmaxobat', [MinmaxobatController::class, 'listminmaxobat']);
    Route::get('/kunjunganpoli', [RajalPoliController::class, 'kunjunganpoli']);
    Route::post('/save-pemeriksaanfisik', [RajalPoliController::class, 'save_pemeriksaanfisik']);
    Route::post('/flagfinish', [RajalPoliController::class, 'flagfinish']);
    Route::post('/terimapasien', [RajalPoliController::class, 'terimapasien']);

    Route::get('/listsuratkontrol', [EditsuratbpjsController::class, 'listsuratkontrol']);
    Route::post('/editsuratkontrol', [EditsuratbpjsController::class, 'editsuratkontrol']);
    Route::post('/jadwal', [EditsuratbpjsController::class, 'jadwaldokter']);

    Route::get('/listrujukankeluarrs', [EditsuratbpjsController::class, 'listrujukankeluarrs']);

    Route::post('/konsulpoli', [RajalPoliController::class, 'konsulpoli']);
});
