<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi\AsuhanKeperawatanController;
use Illuminate\Support\Facades\Route;


Route::group([
  'middleware' => 'auth:api',
  'prefix' => 'simrs/penunjang/ok/asuhan'
], function () {
  Route::get('/keperawatan/getdata', [AsuhanKeperawatanController::class, 'getdata']);
  Route::post('/keperawatan/simpan', [AsuhanKeperawatanController::class, 'simpan']);
  // Route::post('/keperawatan/hapus', [AsuhanKeperawatanController::class, 'simpanPraInduksi']);
});
