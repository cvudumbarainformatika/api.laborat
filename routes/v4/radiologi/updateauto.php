<?php

use App\Http\Controllers\Api\Satusehat\Bundle\KunjunganController;
use App\Http\Controllers\AutoUpdateRadiologiController;
use Illuminate\Support\Facades\Route;



Route::group([
    'prefix' => 'radiologi/updateauto'
], function () {

    Route::get('/batal', [AutoUpdateRadiologiController::class, 'updatebatal']);
});
