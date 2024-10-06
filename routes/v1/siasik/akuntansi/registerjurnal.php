<?php

use App\Http\Controllers\Api\Siasik\Akuntansi\Jurnal\RegJurnalController;
use App\Http\Controllers\Api\Siasik\Akuntansi\JurnalumumController;
use Illuminate\Support\Facades\Route;

Route::group([
    // 'middleware' => 'auth:api',
    'prefix' => 'akuntansi/registerjurnal'
], function () {
    Route::get('/regjurnal', [RegJurnalController::class, 'listjurnal']);

});
