<?php

use App\Http\Controllers\Api\Simrs\Pendaftaran\Ranap\PendaftaranRanapController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran/ranap'
], function () {
    Route::get('list-pendaftararan-ranap', [PendaftaranRanapController::class, 'list_pendaftaran_ranap']);
});
