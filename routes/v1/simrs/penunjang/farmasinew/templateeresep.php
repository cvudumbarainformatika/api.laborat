<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\TemplateEresep\TemplateController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/farmasinew/templateeresep/'
], function () {
    Route::get('cariobat', [TemplateController::class, 'cariobat']);
    Route::post('simpantemplate', [TemplateController::class, 'simpantemplate']);
});
