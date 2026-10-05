<?php

use App\Http\Controllers\Api\Simrs\HomeCare\DokumenHcController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/homecare/dokumen'
], function () {
    Route::get('/catatan', [DokumenHcController::class, 'catatanHomeCare']);
});
