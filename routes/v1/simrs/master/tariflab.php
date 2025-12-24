<?php

use App\Http\Controllers\Api\Simrs\Master\Tarif\PemeriksaanLaboratControllr;
use Illuminate\Support\Facades\Route;


Route::group([
  'middleware' => 'auth:api',
  // 'middleware' => 'jwt.verify',
  'prefix' => 'simrs/master/tarif-laborat'
], function () {
  Route::get('/list', [PemeriksaanLaboratControllr::class, 'list']);
  Route::get('/list-kelompok', [PemeriksaanLaboratControllr::class, 'listKelompok']);
  Route::get('/list-jenis', [PemeriksaanLaboratControllr::class, 'listJenis']);
  Route::post('/simpan', [PemeriksaanLaboratControllr::class, 'simpan']); // diputer dulu ke tabel sementara 
  Route::post('/hapus', [PemeriksaanLaboratControllr::class, 'hidden']);
  Route::post('/tampilkan', [PemeriksaanLaboratControllr::class, 'showAgain']);
});
