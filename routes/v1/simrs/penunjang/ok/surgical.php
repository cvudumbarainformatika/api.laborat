<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi\SurgicalSafetyController;
use Illuminate\Support\Facades\Route;

Route::group([
  'middleware' => 'auth:api',
  // 'middleware' => 'jwt.verify',
  'prefix' => 'simrs/penunjang/surgical'
], function () {
  Route::get('/get-nakes', [SurgicalSafetyController::class, 'getNakes']);
  Route::post('/simpan', [SurgicalSafetyController::class, 'store']);
});
