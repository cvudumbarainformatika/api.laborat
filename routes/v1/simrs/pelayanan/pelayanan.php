<?php

use App\Http\Controllers\Api\Simrs\Bridgingeklaim\EwseklaimController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Anamnesis\AnamnesisController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Diagnosa\DiagnosaKeperawatanController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Diagnosa\DiagnosatransController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Edukasi\EdukasiController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Pemeriksaanfisik\PemeriksaanfisikController;
use App\Http\Controllers\Api\Simrs\Pelayanan\PemeriksaanRMKhusus\PemeriksaankhususMataController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Tindakan\TindakanController;
use App\Http\Controllers\Api\Simrs\Penunjang\DietController;
use App\Http\Controllers\Api\Simrs\Planing\BridbpjsplanController;
use App\Http\Controllers\Api\Simrs\Planing\PlaningController;
use App\Http\Controllers\Api\Simrs\Rajal\PoliController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan'
], function () {
    Route::post('/simpananamnesis', [AnamnesisController::class, 'simpananamnesis']);
    Route::post('/hapusanamnesis', [AnamnesisController::class, 'hapusanamnesis']);
    Route::get('/historyanamnesis', [AnamnesisController::class, 'historyanamnesis']);

    Route::post('/simpanpemeriksaanfisik', [PemeriksaanfisikController::class, 'simpan']);
    Route::post('/hapuspemeriksaanfisik', [PemeriksaanfisikController::class, 'hapuspemeriksaanfisik']);
    Route::post('/simpangambar', [PemeriksaanfisikController::class, 'simpangambar']);
    Route::post('/hapusgambar', [PemeriksaanfisikController::class, 'hapusgambar']);

    Route::post('/hapusdiagnosa', [DiagnosatransController::class, 'hapusdiagnosa']);
    Route::post('/simpandiagnosa', [DiagnosatransController::class, 'simpandiagnosa']);
    Route::get('/listdiagnosa', [DiagnosatransController::class, 'listdiagnosa']);
    Route::get('/diagnosakeperawatan', [DiagnosaKeperawatanController::class, 'diagnosakeperawatan']);

    Route::get('/dialogtindakanpoli', [TindakanController::class, 'dialogtindakanpoli']);
    Route::get('/dialogoperasi', [TindakanController::class, 'dialogoperasi']);
    Route::get('/notatindakan', [TindakanController::class, 'notatindakan']);
    Route::post('/simpantindakanpoli', [TindakanController::class, 'simpantindakanpoli']);
    Route::post('/hapustindakanpoli', [TindakanController::class, 'hapustindakanpoli']);

    Route::post('/ewseklaimrajal_newclaim', [EwseklaimController::class, 'ewseklaimrajal_newclaim']);
    Route::get('/caridiagnosa', [EwseklaimController::class, 'caridiagnosa']);
    Route::get('/carisimulasi', [EwseklaimController::class, 'carisimulasi']);

    Route::get('/mpalningrajal', [PlaningController::class, 'mpalningrajal']);
    Route::get('/mpoli', [PlaningController::class, 'mpoli']);
    Route::post('/simpanplaningpasien', [PlaningController::class, 'simpanplaningpasien']);
    Route::post('/hapusplaningpasien', [PlaningController::class, 'hapusplaningpasien']);
    Route::get('/faskes', [BridbpjsplanController::class, 'faskes']);
    Route::get('/polibpjs', [BridbpjsplanController::class, 'polibpjs']);

    Route::post('/simpanedukasi', [EdukasiController::class, 'simpanedukasi']);
    Route::post('/hapusedukasi', [EdukasiController::class, 'hapusedukasi']);
    Route::get('/mpenerimaedukasi', [EdukasiController::class, 'mpenerimaedukasi']);
    Route::get('/mkebutuhanedukasi', [EdukasiController::class, 'mkebutuhanedukasi']);

    Route::get('/listdokter', [PoliController::class, 'listdokter']);
    Route::post('/gantidpjp', [PoliController::class, 'gantidpjp']);

    Route::post('/pemeriksaanmatakhusus', [PemeriksaankhususMataController::class, 'pemeriksaanmatakhusus']);

    Route::get('/bridbpjslistrujukan', [BridbpjsplanController::class, 'bridbpjslistrujukan']);
    Route::get('/icare', [PoliController::class, 'icare']);

    Route::get('/masterdiet', [DietController::class, 'masterdiet']);
    Route::post('/simpandiet', [DietController::class, 'simpandiet']);
    Route::post('/hapusdiet', [DietController::class, 'hapusdiet']);

    // Route::get('/cariprocedure', [EwseklaimController::class, 'cariprocedure']);
});
