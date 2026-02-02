<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi\TindakanDanLaporanController;
use Illuminate\Support\Facades\Route;

Route::group([
  'middleware' => 'auth:api',
  // 'middleware' => 'jwt.verify',
  'prefix' => 'simrs/penunjang/ok'
], function () {
  Route::get('/tindakan-op/get-tindakan-op', [TindakanDanLaporanController::class, 'getTindakanOp']);
  Route::post('/tindakan-op/simpan', [TindakanDanLaporanController::class, 'simpanTindakanOp']);
  Route::post('/tindakan-op/hapus', [TindakanDanLaporanController::class, 'hapusTindakanOp']);
  Route::post('/laporan-op/simpan', [TindakanDanLaporanController::class, 'simpanLaporan']);
  Route::post('/laporan-op/hapus', [TindakanDanLaporanController::class, 'hapusLaporannOp']);
});
