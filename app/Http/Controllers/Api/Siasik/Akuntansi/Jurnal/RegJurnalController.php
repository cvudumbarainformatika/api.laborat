<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi\Jurnal;

use App\Http\Controllers\Controller;
use App\Models\Siasik\TransaksiLS\NpkLS_heder;
use App\Models\Siasik\TransaksiLS\NpkLS_rinci;
use App\Models\Siasik\TransaksiLS\Serahterima_header;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastrinciM;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
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
        )
        ->when(request('q'),function ($query) {
            $query
            ->where('noserahterimapekerjaan', 'LIKE', '%' . request('q') . '%')
            ->orWhere('tgltrans', 'LIKE', '%' . request('q') . '%')
            ->orWhere('nokontrak', 'LIKE', '%' . request('q') . '%')
            ->orWhere('kegiatanblud', 'LIKE', '%' . request('q') . '%');
        })
        ->where('kunci', '!=', '')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->with(['rinci'=>function($rinci){
            $rinci->select('serahterima50.noserahterimapekerjaan',
                        'serahterima50.koderek50',
                        'serahterima50.uraianrek50',
                        'serahterima50.itembelanja',
                        'serahterima50.nominalpembayaran')
                        ->when(request('q'),function ($query) {
                            $query
                            ->where('nominalpembayaran', 'LIKE', '%' . request('q') . '%')
                            ->orWhere('koderek50', 'LIKE', '%' . request('q') . '%')
                            ->orWhere('uraianrek50', 'LIKE', '%' . request('q') . '%')
                            ->orWhere('itembelanja', 'LIKE', '%' . request('q') . '%');
                        })
                        ->with('jurnal', function($jurnal){
                            $jurnal->select('akun_mapjurnal.kodeall',
                                    'akun_mapjurnal.kode50',
                                    'akun_mapjurnal.uraian50',
                                    'akun_mapjurnal.kode_bast',
                                    'akun_mapjurnal.uraian_bast',
                                    'akun_mapjurnal.kode_bastx',
                                    'akun_mapjurnal.uraian_bastx',)
                                    ->when(request('q'),function ($query) {
                                        $query
                                        ->where('kode50', 'LIKE', '%' . request('q') . '%')
                                        ->orWhere('uraian50', 'LIKE', '%' . request('q') . '%')
                                        ->orWhere('kode_bast', 'LIKE', '%' . request('q') . '%')
                                        ->orWhere('uraian_bast', 'LIKE', '%' . request('q') . '%')
                                        ->orWhere('kode_bastx', 'LIKE', '%' . request('q') . '%')
                                        ->orWhere('uraian_bastx', 'LIKE', '%' . request('q') . '%');
                                    });
                        });
                // ->selectRaw('sum(nominalpembayaran) as total');
        }])
        ->orderBy('tgltrans', 'desc')
        ->get();

        $bastfarmasi=PenerimaanHeder::select('penerimaan_h.nobast',
                                            'penerimaan_h.tgl_bast',
                                            'penerimaan_h.jenis_penerimaan',
                                            'penerimaan_h.kdpbf',
                                            'penerimaan_h.no_npd',)

        ->where('nobast', '!=', '')
        // ->where('no_npd', '=', '')
        // ->whereNotNull('tgl_bast')
        ->whereIn('jenis_penerimaan', ['Pesanan'])
        ->when(request('q'),function ($query) {
            $query->where('nobast', 'LIKE', '%' . request('q') . '%');
        })
        ->with('rincianbast', function($rinci) use ($tahun) {
            $rinci->select('bast_r.nobast',
                            'bast_r.nopenerimaan',
                            'bast_r.kdobat',
                            'bast_r.harga_net',
                            'bast_r.jumlah',
                            'bast_r.subtotal'
                            // DB::raw('(harga_net * jumlah) as totalobat')
                            )
                    ->with('masterobat',function ($rekening) use ($tahun){
                        $rekening->select('new_masterobat.kd_obat',
                                        'new_masterobat.kode50',
                                        'new_masterobat.uraian50',
                                        'new_masterobat.kode108',
                                        'new_masterobat.uraian108')
                            ->with('jurnal');
                    });
        })
        // ->orderBy('tgl_bast', 'DESC')
        ->whereBetween('tgl_bast', [$awal, $akhir])
        ->orderBy('nobast', 'asc')
        ->groupBy('nobast')
        ->get();

        $cairnonstp=NpkLS_heder::select('nonpk','tglpindahbuku')
        ->with('npdls', function ($npd){
            $npd->select('nonpk',
                        'nonpdls',
                        'serahterimapekerjaan',
                        'kegiatanblud',
                        'nopencairan')
            ->where('nopencairan', '!=', '');
            // if('serahterimapekerjaan' !== '1'){
            //     $npd->with('npdlsrinci', function($x){
            //         $x->select('nonpdls','koderek50','nominalpembayaran')
            //         ->with('mapjurnal',function($sel){
            //             $sel->select('kodeall',
            //                     'kode50',
            //                     'kode_bastcair1',
            //                     'uraian_bastcair1',
            //                     'kode_bastcairx',
            //                     'uraian_bastcairx',
            //                     'kode_bastcair2',
            //                     'uraian_bastcair2',);
            //         });
            //     });
            // }else{
            //     $npd->with('npdlsrinci', function($x){
            //         $x->select('nonpdls','koderek50','nominalpembayaran')
            //         ->with('mapjurnal',function($sel){
            //             $sel->select('kodeall',
            //                     'kode50',
            //                     'kode_cair1',
            //                     'uraian_cair1',
            //                     'kode_cairx',
            //                     'uraian_cairx',
            //                     'kode_cair2',
            //                     'uraian_cair2',);
            //         });
            //     });
            // }
        })
        ->whereBetween('tglpindahbuku', [$awal, $akhir])
        ->get();

        $regjurnal = [
            'stp' => $stp,
            'bastfarmasi' => $bastfarmasi,
            'pencairan' => $cairnonstp,

        ];
        return new JsonResponse($regjurnal);
    }
}
