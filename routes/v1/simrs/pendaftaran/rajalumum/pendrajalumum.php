<?php

use App\Http\Controllers\Api\Simrs\Historypasien\HistorypasienController;
use App\Http\Controllers\Api\Simrs\Laporan\IT\LaporanAntianRsDanBpjsController;
use App\Http\Controllers\Api\Simrs\Master\PasienController;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal\DaftarrajalController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran'
], function () {

    //simpan rs17  ==> rajalumumsimpan
    Route::post('/tambah-antrian', [DaftarrajalController::class, 'tambahAntrian']);
    Route::post('/simpandaftar', [DaftarrajalController::class, 'simpankunjunganpoli']);
    Route::get('/masterpasien', [PasienController::class, 'listpasien']);
    Route::get('/cek-data-pasien', [PasienController::class, 'cekDataPasien']);
    Route::get('/historypasien', [HistorypasienController::class, 'historykunjunganpasien']);

    // Route::get('/kunjunganpasienbpjs', [DaftarrajalController::class, 'daftarkunjunganpasienbpjs']);
    Route::post('/kunjunganpasienbpjs', [DaftarrajalController::class, 'daftarkunjunganpasienbpjs']); // ini karena payload noka pasien dikirim dari front, front yang nampung data
    Route::get('/antrianmobilejkn', [DaftarrajalController::class, 'antrianmobilejkn']);
    Route::get('/caripasien', [PasienController::class, 'caripasien']);
    Route::get('/caripasienbyrm', [PasienController::class, 'caripasienbyrm']);

    Route::get('/listkonsulantarpoli', [DaftarrajalController::class, 'listkonsulantarpoli']);

    Route::get('/umum/kunjunganpasienumum', [DaftarrajalController::class, 'daftarkunjunganpasienumum']);

    Route::post('/hapuspasien', [DaftarrajalController::class, 'hapuspasien']);



    // cari rujukan keluar rs
    Route::post('/cari-rujukan-keluar', [DaftarrajalController::class, 'caruRujukanKeluarRs']);
    Route::post('/cari-antrian-bpjs', [LaporanAntianRsDanBpjsController::class, 'getListBpjsPost']);
    Route::post('/kirim-ulang-taskid', [LaporanAntianRsDanBpjsController::class, 'kirimUlangTaskId']);
});
