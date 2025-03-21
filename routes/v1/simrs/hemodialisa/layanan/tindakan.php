<?php

use App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan\TindakanHemodialisaController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/hemodialisa/layanan/tindakan'
], function () {
    Route::post('/simpantindakan-hd', [TindakanHemodialisaController::class, 'getTindakanHd']);
    Route::get('/listtindakan-hd', [TindakanHemodialisaController::class, 'getTindakanRanap']); // fixed
});
