<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\SetNewStokController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/stok'
], function () {
    Route::get('/new-stok', [SetNewStokController::class, 'setNewStok']);
    Route::get('/new-stok-opname', [SetNewStokController::class, 'setStokOpnameAwal']);
});
