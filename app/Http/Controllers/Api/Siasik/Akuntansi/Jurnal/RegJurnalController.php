<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi\Jurnal;

use App\Http\Controllers\Controller;
use App\Models\Siasik\TransaksiLS\Serahterima_header;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegJurnalController extends Controller
{
    public function listjurnal()
    {
        $awal=request('tahun').'-'. request('bulan').'-01';
        $akhir=request('tahun').'-'. request('bulan').'-31';
        $tahun=date('Y');
        $stp = Serahterima_header::select(
            'serahterima_heder.noserahterimapekerjaan',
            'serahterima_heder.nokontrak',
            'serahterima_heder.tgltrans',
            'serahterima_heder.kegiatanblud',
            'serahterima_heder.nopencairan',
        )->where('nopencairan', '=', '')
        ->where('kunci', '!=', '')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->with(['rinci'=>function($rinci){
            $rinci->select('serahterima50.noserahterimapekerjaan',
                        'serahterima50.koderek50',
                        'serahterima50.uraianrek50',
                        'serahterima50.itembelanja',
                        'serahterima50.nominalpembayaran')
                        ->with('jurnal', function($jurnal){
                            $jurnal->select('akun_jurnal.kodeall2',
                                    'akun_jurnal.kode_lra',
                                    'akun_jurnal.uraian_lra',
                                    'akun_jurnal.kode_lo',
                                    'akun_jurnal.uraian_lo',
                                    'akun_jurnal.kode_neraca2',
                                    'akun_jurnal.uraian_neraca2');
                        });
                // ->selectRaw('sum(nominalpembayaran) as total');
        }])
        ->orderBy('tgltrans', 'desc')
        ->get();
        return new JsonResponse($stp);
    }
}
