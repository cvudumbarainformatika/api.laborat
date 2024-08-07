<?php

use App\Http\Controllers\Api\Simrs\Pendaftaran\Ranap\PendaftaranRanapController;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Ranap\SepranapController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran/ranap'
], function () {
    Route::post('get-rujukan-bridging-by-noka', [SepranapController::class, 'getRujukanBridgingByNoka']);
    Route::post('get-ppk-rujukan', [SepranapController::class, 'getPpkRujukan']);
    Route::post('get-diagnosa-bpjs', [SepranapController::class, 'getDiagnosaBpjs']);
    Route::post('get-propinsi-bpjs', [SepranapController::class, 'getPropinsiBpjs']);
    Route::post('get-kabupaten-bpjs', [SepranapController::class, 'getKabupatenBpjs']);
    Route::post('get-kecamatan-bpjs', [SepranapController::class, 'getKecamatanBpjs']);
    Route::post('get-dpjp-bpjs', [SepranapController::class, 'getDpjpBpjs']);
    Route::post('create-sep-ranap', [SepranapController::class, 'create_sep_ranap']);
    Route::post('list-rujukan-peserta', [SepranapController::class, 'getListRujukanPeserta']);
    Route::post('get-list-spri', [SepranapController::class, 'getListSpri']);
    Route::post('get-suplesi-jasa-raharja-by-bpjs', [SepranapController::class, 'getSuplesi']);
});
