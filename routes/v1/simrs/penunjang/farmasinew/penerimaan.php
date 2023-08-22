<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/penerimaan'
], function () {
    Route::get('/dialogpemesananobat', [PenerimaanController::class, 'listpemesananfix']);
    Route::post('/simpan', [PenerimaanController::class, 'simpanpenerimaan']);
});
