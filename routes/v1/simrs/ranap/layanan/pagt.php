<?php

use App\Http\Controllers\Api\Simrs\Gizi\PagtController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/layanan/pagt'
], function () {

  Route::get('/list', [PagtController::class, 'list']);
  Route::post('/simpan', [PagtController::class, 'simpan']);

});
