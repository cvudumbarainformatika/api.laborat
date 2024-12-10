<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang\PengembalianPinjamanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/farmasinew/pengembalian'
], function () {
    Route::get('/get-pbf', [PengembalianPinjamanController::class, 'getPbfPeminjam']);
    Route::get('/get-noper', [PengembalianPinjamanController::class, 'getNopenerimaan']);
});
