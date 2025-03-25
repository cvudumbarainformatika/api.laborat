<?php

use App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan\IntradialitikController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/hemodialisa/layanan/intradialitik'
], function () {
    Route::post('/simpan', [IntradialitikController::class, 'simpan']);
    Route::get('/list', [IntradialitikController::class, 'list']);
});
