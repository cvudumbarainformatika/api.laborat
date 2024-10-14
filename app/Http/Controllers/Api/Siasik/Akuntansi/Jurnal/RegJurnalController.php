<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi\Jurnal;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\TransaksiLS\Contrapost;
use App\Models\Siasik\TransaksiLS\NpdLS_heder;
use App\Models\Siasik\TransaksiLS\NpkLS_heder;
use App\Models\Siasik\TransaksiLS\NpkLS_rinci;
use App\Models\Siasik\TransaksiLS\Serahterima_header;
use App\Models\Siasik\TransaksiPjr\Nihil;
use App\Models\Siasik\TransaksiPjr\SPM_GU;
use App\Models\Siasik\TransaksiPjr\SpmUP;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastrinciM;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\returnValue;

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

        $cairstp=NpkLS_heder::select('npdls_heder.nonpk',
                'npdls_heder.nonpdls',
                'npdls_heder.serahterimapekerjaan',
                'npdls_heder.kegiatanblud',
                'npdls_heder.nopencairan',
                'npdls_rinci.nonpdls',
                'npdls_rinci.koderek50',
                'npdls_rinci.rincianbelanja',
                'npdls_rinci.nominalpembayaran',
                'npkls_heder.tglpindahbuku',
                'akun_mapjurnal.kd_blud',
                'akun_mapjurnal.ur_blud',
                'akun_mapjurnal.kode50',
                'akun_mapjurnal.kode_bastcair1',
                'akun_mapjurnal.uraian_bastcair1',
                'akun_mapjurnal.kode_bastcairx',
                'akun_mapjurnal.uraian_bastcairx',
                'akun_mapjurnal.kode_bastcair2',
                'akun_mapjurnal.uraian_bastcair2',
                DB::raw('sum(npdls_rinci.nominalpembayaran) as total'))
        ->groupBy('npdls_rinci.koderek50','npdls_rinci.nonpdls')
        ->join('npdls_heder', 'npdls_heder.nonpk', '=','npkls_heder.nonpk')
        ->join('npdls_rinci', 'npdls_rinci.nonpdls', '=','npdls_heder.nonpdls')
        ->join('akun_mapjurnal', 'akun_mapjurnal.kodeall', '=','npdls_rinci.koderek50')
        ->where('npdls_heder.nopencairan', '!=', '')
        ->whereIn('npdls_heder.serahterimapekerjaan',['1', '3'])
        ->whereBetween('npkls_heder.tglpindahbuku', [$awal, $akhir])
        ->orderBy('npkls_heder.tglpindahbuku', 'asc')
        ->get();

        $cairnostp=NpkLS_heder::select('npdls_heder.nonpk',
                    'npdls_heder.nonpdls',
                    'npdls_heder.serahterimapekerjaan',
                    'npdls_heder.kegiatanblud',
                    'npdls_heder.nopencairan',
                    'npdls_rinci.nonpdls',
                    'npdls_rinci.koderek50',
                    'npdls_rinci.rincianbelanja',
                    'npdls_rinci.nominalpembayaran',
                    'npkls_heder.tglpindahbuku',
                    'akun_mapjurnal.kd_blud',
                    'akun_mapjurnal.ur_blud',
                    'akun_mapjurnal.kode50',
                    'akun_mapjurnal.kode_cair1',
                    'akun_mapjurnal.uraian_cair1',
                    'akun_mapjurnal.kode_cairx',
                    'akun_mapjurnal.uraian_cairx',
                    'akun_mapjurnal.kode_cair2',
                    'akun_mapjurnal.uraian_cair2',
                    DB::raw('sum(npdls_rinci.nominalpembayaran) as total'))
        ->groupBy('npdls_rinci.koderek50','npdls_rinci.nonpdls')
        ->join('npdls_heder', 'npdls_heder.nonpk', '=','npkls_heder.nonpk')
        ->join('npdls_rinci', 'npdls_rinci.nonpdls', '=','npdls_heder.nonpdls')
        ->join('akun_mapjurnal', 'akun_mapjurnal.kodeall', '=','npdls_rinci.koderek50')
        ->where('npdls_heder.nopencairan', '!=', '')
        ->whereIn('npdls_heder.serahterimapekerjaan',['2'])
        ->whereBetween('npkls_heder.tglpindahbuku', [$awal, $akhir])
        ->orderBy('npkls_heder.tglpindahbuku', 'asc')
        ->get();

        $contrapost = Contrapost::select('contrapost.nocontrapost',
                    'contrapost.tglcontrapost',
                    'contrapost.kegiatanblud',
                    'contrapost.koderek50',
                    'contrapost.rincianbelanja',
                    'contrapost.nominalcontrapost',
                    'akun_mapjurnal.kode50',
                    'akun_mapjurnal.kode_cair1',
                    'akun_mapjurnal.uraian_cair1',
                    'akun_mapjurnal.kode_cairx',
                    'akun_mapjurnal.uraian_cairx',
                    'akun_mapjurnal.kode_cair2',
                    'akun_mapjurnal.uraian_cair2',
                    'akun_mapjurnal.kd_blud',
                    'akun_mapjurnal.ur_blud')
        ->join('akun_mapjurnal', 'akun_mapjurnal.kodeall', '=','contrapost.koderek50')
        ->whereBetween('contrapost.tglcontrapost', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        ->orderBy('contrapost.tglcontrapost', 'asc')
        ->get();

        $spmup = SpmUP::select('transSpm.noSpm',
            'transSpm.tglSpm',
            'transSpm.uraianPekerjaan',
            'transSpm.jumlahspp')
        ->whereBetween('transSpm.tglSpm', [$awal, $akhir])
        ->get();

        $spmgu = SPM_GU::select('transSpmgu.noSpm',
            'transSpmgu.tglSpm',
            'transSpmgu.uraianPekerjaan',
            'transSpmgu.jumlahspp')
        ->whereBetween('transSpmgu.tglSpm', [$awal, $akhir])
        ->get();

        $nihil = Nihil::select('pengembalianup.nopengembalian',
        'pengembalianup.tgltrans',
        'pengembalianup.jmlpengembalianreal')
        ->whereBetween('pengembalianup.tgltrans', [$awal, $akhir])
        ->get();
        $regjurnal = [
            'stp' => $stp,
            'bastfarmasi' => $bastfarmasi,
            'cair_stp' => $cairstp,
            'cair_nostp' => $cairnostp,
            'contrapost' => $contrapost,
            'spmup' => $spmup,
            'spmgu' => $spmgu,
            'nihil' => $nihil
        ];
        return new JsonResponse($regjurnal);
    }
}
