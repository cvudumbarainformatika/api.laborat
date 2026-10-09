<?php

use App\Http\Controllers\Api\Simrs\Igd\TtddokumenIgdController;
use App\Http\Controllers\Api\Simrs\Kasir\BillingbynoregController;
use App\Http\Controllers\Api\Simrs\Kasir\CariKarcisController;
use App\Http\Controllers\Api\Simrs\Kasir\CariKwitansinonTunai;
use App\Http\Controllers\Api\Simrs\Kasir\CreateTbpController;
use App\Http\Controllers\Api\Simrs\Kasir\FlagingManualVaController;
use App\Http\Controllers\Api\Simrs\Kasir\IgdController;
use App\Http\Controllers\Api\Simrs\Kasir\IgdPaymentController;
use App\Http\Controllers\Api\Simrs\Kasir\KasirrajalController;
use App\Http\Controllers\Api\Simrs\Kasir\PasienLuarController;
use App\Http\Controllers\Api\Simrs\Kasir\UangJaminanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/kasir'
], function () {
    Route::get('/rajal/cari-karcis', [CariKarcisController::class, 'carikarcis']);
     Route::get('/rajal/carikonsulantarpoli', [CariKarcisController::class, 'carikonsulantarpoli']);
    Route::get('/rajal/cari-obat', [CariKarcisController::class, 'cariobat']);
    Route::get('/rajal/cari-tindakan', [CariKarcisController::class, 'caritindakan']);
    Route::get('/rajal/cari-tindakan-psikologi', [CariKarcisController::class, 'caritindakanpsikologi']);
    Route::get('/rajal/cari-tindakan-operasi', [CariKarcisController::class, 'caritindakanoperasi']);
    Route::get('/rajal/cari-laborat', [CariKarcisController::class, 'carilaborat']);
    Route::get('/rajal/cari-radiologi', [CariKarcisController::class, 'cariradiologi']);
    Route::get('/rajal/cari-sharingbpjs', [CariKarcisController::class, 'getSharingRajal']);
    Route::get('/rajal/getKwitansinontunai', [CariKwitansinonTunai::class, 'getKwitansinontunai']);
    Route::get('/rajal/rekapbillbynoreg', [BillingbynoregController::class, 'rekapbillbynoreg']);

    Route::get('/rajal/getkwitansiterbayar', [BillingbynoregController::class, 'getkwitansiterbayar']);

    Route::get('/rajal/kunjunganpoli', [KasirrajalController::class, 'kunjunganpoli']);
    Route::get('/rajal/billbynoregx', [BillingbynoregController::class, 'billbynoregrajalx']);

    Route::get('/rajal/tagihanpergolongan', [KasirrajalController::class, 'tagihanpergolongan']);
    Route::post('/rajal/pembayarankarcis', [KasirrajalController::class, 'pembayarankarcis']);

     Route::post('/rajal/pembayaran-non-karcis', [KasirrajalController::class, 'pembayarannonkarcis']);


    // kasir igd
    Route::get('/igd/billbynoreg', [BillingbynoregController::class, 'billbynoregigd']);
    Route::get('/igd/pasien-pulang', [IgdController::class, 'pasienPulang']);
    Route::get('/igd/rincian-pembayaran', [IgdPaymentController::class, 'rincianPembayaran']);
    Route::get('/igd/riwayat-kwitansi', [IgdPaymentController::class, 'riwayatKwitansi']);
    Route::get('/igd/cek-kwitansi-pembayaran', [IgdPaymentController::class, 'cekKwitansiPembayaran']);
    Route::post('/igd/batal-kwitansi', [IgdPaymentController::class, 'batalKwitansi']);
    Route::post('/igd/hapus-pembayaran', [IgdPaymentController::class, 'hapusPembayaran']);
    Route::post('/igd/cetak-kwitansi', [IgdPaymentController::class, 'cetakKwitansi']);
    Route::post('/igd/simpan-pembayaran', [IgdPaymentController::class, 'simpanPembayaran']);

    Route::get('/va/listva', [FlagingManualVaController::class, 'listva']);
    Route::post('/va/flagingmanualva', [FlagingManualVaController::class, 'flagingmanual']);
    Route::post('/va/batal-va', [FlagingManualVaController::class, 'batalva']);

    Route::get('/uang-jaminan', [UangJaminanController::class, 'index']);
    Route::get('/uang-jaminan/pasien', [UangJaminanController::class, 'pasien']);
    Route::post('/uang-jaminan', [UangJaminanController::class, 'simpan']);

    Route::post('/rajal/batalkwitansi', [KasirrajalController::class, 'batalkwitansi']);

    Route::get('/tbp/masterkasir', [CreateTbpController::class, 'masterkasir']);
    Route::get('/tbp/getdatatbp', [CreateTbpController::class, 'getdatatbp']);
    Route::get('/tbp/cariKwitansi', [CreateTbpController::class, 'cariKwitansi']);
    Route::get('/tbp/getmasterkasir', [CreateTbpController::class, 'getmasterkasir']);
    Route::post('/tbp/simpanTbp', [CreateTbpController::class, 'createnotatbp']);
    Route::post('/tbp/batal', [CreateTbpController::class, 'batalTbp']);
    Route::get('/tbp/getrincianTbp', [CreateTbpController::class, 'getrincianTbp']);
    Route::post('/tbp/hapusTbp', [CreateTbpController::class, 'getbataltbp']);


    Route::get('/pasien-luar/getbillpasienluar', [PasienLuarController::class, 'getbill']);

     Route::post('/rajal/simpanttddokumen', [TtddokumenIgdController::class, 'store']);
});
