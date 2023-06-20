<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\BentuksediaanController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\KandungannamagenerikController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\KekuatandosisController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\MjenisperbekalanController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\MkodebelanjaController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\ObatnewController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\VolumesediaanController;
use App\Models\Pegawai\Akses\Role;
use App\Models\Simrs\Penunjang\Farmasinew\Mkelompokpenyimpanan;
use App\Models\Simrs\Penunjang\Farmasinew\Mmerk;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasi/master'
], function () {
    Route::post('/simpanjenisperbekalan', [MjenisperbekalanController::class, 'simpan']);
    Route::post('/hapusjenisperbekalan', [MjenisperbekalanController::class, 'hapus']);
    Route::get('/listmjenisperbekalan', [MjenisperbekalanController::class, 'list']);

    Route::post('/simpankodebelanjaobat', [MkodebelanjaController::class, 'simpan']);
    Route::post('/hapuskodebelanjaobat', [MkodebelanjaController::class, 'hapus']);
    Route::get('/listkodebelanjaobat', [MkodebelanjaController::class, 'list']);

    Route::post('/simpankandungan_namagenerik', [KandungannamagenerikController::class, 'simpan']);
    Route::post('/hapuskandungan_namagenerik', [KandungannamagenerikController::class, 'hapus']);
    Route::get('/listkandungan_namagenerik', [KandungannamagenerikController::class, 'list']);

    Route::post('/simpanbentuksediaan', [BentuksediaanController::class, 'simpan']);
    Route::post('/hapusbentuksediaan', [BentuksediaanController::class, 'hapus']);
    Route::get('/listbentuksediaan', [BentuksediaanController::class, 'list']);

    Route::post('/simpankekuatandosis', [KekuatandosisController::class, 'simpan']);
    Route::post('/hapuskekuatandosis', [KekuatandosisController::class, 'hapus']);
    Route::get('/listkekuatandosis', [KekuatandosisController::class, 'list']);

    Route::post('/simpanvolumesediaan', [VolumesediaanController::class, 'simpan']);
    Route::post('/hapusvolumesediaan', [VolumesediaanController::class, 'hapus']);
    Route::get('/listvolumesediaan', [VolumesediaanController::class, 'list']);

    Route::post('/simpanobat', [ObatnewController::class, 'simpan']);
    Route::post('/hapusobat', [ObatnewController::class, 'hapus']);
    Route::get('/listobat', [ObatnewController::class, 'list']);

    Route::post('/simpanmerk', [Mmerk::class, 'simpan']);
    Route::post('/hapusmerk', [Mmerk::class, 'hapus']);
    Route::get('/listmerk', [Mmerk::class, 'list']);

    Route::post('/simpankelompokpenyimpanan', [Mkelompokpenyimpanan::class, 'simpan']);
    Route::post('/hapuskelompokpenyimpanan', [Mkelompokpenyimpanan::class, 'hapus']);
    Route::get('/listkelompokpenyimpanan', [Mkelompokpenyimpanan::class, 'list']);
});
