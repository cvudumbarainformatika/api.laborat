<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\DepoController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/depo'
], function () {
    Route::get('/lihatstokgudang', [DepoController::class, 'lihatstokgudang']);
});
