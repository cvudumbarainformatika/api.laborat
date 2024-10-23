<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi\Jurnal;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Akuntansi\Jurnal\Create_JurnalPosting;
use App\Models\Siasik\Akuntansi\Jurnal\Jurnal_Posting;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\TransaksiLS\Contrapost;
use App\Models\Siasik\TransaksiLS\NpdLS_heder;
use App\Models\Siasik\TransaksiLS\NpkLS_heder;
use App\Models\Siasik\TransaksiLS\NpkLS_rinci;
use App\Models\Siasik\TransaksiLS\Serahterima_header;
use App\Models\Siasik\TransaksiLS\TransPajak;
use App\Models\Siasik\TransaksiPjr\Nihil;
use App\Models\Siasik\TransaksiPjr\SpjPanjar_Header;
use App\Models\Siasik\TransaksiPjr\SpjPanjar_Rinci;
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
            'serahterima_heder.kunci',
            'serahterima50.noserahterimapekerjaan',
            'serahterima50.koderek50',
            'serahterima50.uraianrek50',
            'serahterima50.itembelanja',
            'serahterima50.nominalpembayaran',
            'akun_mapjurnal.kodeall',
            'akun_mapjurnal.kode50',
            'akun_mapjurnal.uraian50',
            'akun_mapjurnal.kode_bast',
            'akun_mapjurnal.uraian_bast',
            'akun_mapjurnal.kode_bastx',
            'akun_mapjurnal.uraian_bastx'
        )
        ->join('serahterima50', 'serahterima50.noserahterimapekerjaan', 'serahterima_heder.noserahterimapekerjaan')
        ->join('akun_mapjurnal', 'akun_mapjurnal.kodeall', 'serahterima50.koderek50')
        ->when(request('q'),function ($query) {
            $query
            ->where('serahterima_heder.noserahterimapekerjaan', 'LIKE', '%' . request('q') . '%')
            ->orWhere('serahterima_heder.tgltrans', 'LIKE', '%' . request('q') . '%')
            ->orWhere('serahterima_heder.kegiatanblud', 'LIKE', '%' . request('q') . '%');
        })
        ->where('serahterima_heder.kunci', '!=', '')
        ->whereBetween('serahterima_heder.tgltrans', [$awal, $akhir])
        // ->with(['rinci'=>function($rinci){
        //     $rinci->select('serahterima50.noserahterimapekerjaan',
        //                 'serahterima50.koderek50',
        //                 'serahterima50.uraianrek50',
        //                 'serahterima50.itembelanja',
        //                 'serahterima50.nominalpembayaran')
        //                 ->when(request('q'),function ($query) {
        //                     $query
        //                     ->where('nominalpembayaran', 'LIKE', '%' . request('q') . '%')
        //                     ->orWhere('koderek50', 'LIKE', '%' . request('q') . '%')
        //                     ->orWhere('uraianrek50', 'LIKE', '%' . request('q') . '%')
        //                     ->orWhere('itembelanja', 'LIKE', '%' . request('q') . '%');
        //                 })
        //                 ->with('jurnal', function($jurnal){
        //                     $jurnal->select('akun_mapjurnal.kodeall',
        //                             'akun_mapjurnal.kode50',
        //                             'akun_mapjurnal.uraian50',
        //                             'akun_mapjurnal.kode_bast',
        //                             'akun_mapjurnal.uraian_bast',
        //                             'akun_mapjurnal.kode_bastx',
        //                             'akun_mapjurnal.uraian_bastx',)
        //                             ->when(request('q'),function ($query) {
        //                                 $query
        //                                 ->where('kode50', 'LIKE', '%' . request('q') . '%')
        //                                 ->orWhere('uraian50', 'LIKE', '%' . request('q') . '%')
        //                                 ->orWhere('kode_bast', 'LIKE', '%' . request('q') . '%')
        //                                 ->orWhere('uraian_bast', 'LIKE', '%' . request('q') . '%')
        //                                 ->orWhere('kode_bastx', 'LIKE', '%' . request('q') . '%')
        //                                 ->orWhere('uraian_bastx', 'LIKE', '%' . request('q') . '%');
        //                             });
        //                 });
        //         // ->selectRaw('sum(nominalpembayaran) as total');
        // }])
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

        // PENCAIRAN STP //
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

        // PENCAIRAN TANPA STP //
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



        $pajakls = TransPajak::select('npdls_pajak.nonpdls',
                'npdls_pajak.pph21',
                'npdls_pajak.pph22',
                'npdls_pajak.pph23',
                'npdls_pajak.pph25',
                'npdls_pajak.pasal4',
                'npdls_pajak.ppnpusat',
                'npdls_pajak.pajakdaerah',
                'npdls_heder.nonpk',
                'npdls_heder.kegiatanblud',
                'npkls_heder.tglpindahbuku')
        ->join('npdls_heder', 'npdls_heder.nonpdls', 'npdls_pajak.nonpdls')
        ->join('npkls_heder', 'npkls_heder.nonpk', 'npdls_heder.nonpk')
        ->whereBetween('npkls_heder.tglpindahbuku', [$awal, $akhir])
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

        $spjpanjar=SpjPanjar_Header::select('spjpanjar_heder.nospjpanjar',
                    'spjpanjar_heder.tglspjpanjar',
                    'spjpanjar_heder.kegiatanblud',
                    'spjpanjar_rinci.nospjpanjar',
                    'spjpanjar_rinci.koderek50',
                    'spjpanjar_rinci.rincianbelanja50',
                    'spjpanjar_rinci.jumlahbelanjapanjar',
                    'akun_mapjurnal.kode50',
                    'akun_mapjurnal.kode_cair1',
                    'akun_mapjurnal.uraian_cair1',
                    'akun_mapjurnal.kode_cairx',
                    'akun_mapjurnal.uraian_cairx',
                    'akun_mapjurnal.kode_cair2',
                    'akun_mapjurnal.uraian_cair2')
        ->join('spjpanjar_rinci', 'spjpanjar_rinci.nospjpanjar', '=','spjpanjar_heder.nospjpanjar')
        ->join('akun_mapjurnal', 'akun_mapjurnal.kodeall', '=','spjpanjar_rinci.koderek50')
        ->whereBetween('spjpanjar_heder.tglspjpanjar', [$awal, $akhir])
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
            'pajakls' => $pajakls,
            'contrapost' => $contrapost,
            'spmup' => $spmup,
            'spmgu' => $spmgu,
            'nihil' => $nihil,
            'spjpanjar' => $spjpanjar,
        ];
        return new JsonResponse($regjurnal);
    }


    public function savejurnal(Request $request){
        // return $data;
        DB::beginTransaction();
        try {
            $data = [];
            foreach($request->jurnal as $post){
                $notrans = [
                    'notrans'=>$post['notrans'],
                    'tanggal'=>$post['tanggal'],
                    'kegiatan'=>$post['kegiatan'],
                    'keterangan'=>$post['keterangan'],
                    'kode'=>$post['kode'],
                    'uraian'=>$post['uraian'],
                    'debit'=>$post['debit'],
                    'kredit'=>$post['kredit'],
                ];
                $detail = [

                    // 'd_pjk'=>$post['d_pjk'],
                    // 'k_pjk'=>$post['k_pjk'],
                    // 'd_pjk1'=>$post['d_pjk1'],
                    // 'k_pjk1'=>$post['k_pjk1'],
                ];
                $hasil = [
                    'notrans'=>$post['notrans'],
                    'tanggal'=>$post['tanggal'],
                    'kegiatan'=>$post['kegiatan'],
                    'keterangan'=>$post['keterangan'],
                    'kode'=>$post['kode'],
                    'uraian'=>$post['uraian'],
                    'debit'=>$post['debit'],
                    'kredit'=>$post['kredit'],
                    // 'd_pjk'=>$post['d_pjk'],
                    // 'k_pjk'=>$post['k_pjk'],
                    // 'd_pjk1'=>$post['d_pjk1'],
                    // 'k_pjk1'=>$post['k_pjk1'],

                ];
                Create_JurnalPosting::updateOrCreate($notrans, $detail);
                $data[]=$hasil;
            }
            // Jurnal_Posting::upsert([$data],
            // [
            //     'notrans', 'tanggal', 'kegiatan', 'keterangan'
            // ],
            // [
            //     'debit', 'kredit'
            // ]);

            DB::commit();
            return new JsonResponse(
                [
                    'message' => 'Data Berhasil disimpan...!!!',
                    'result' => $data
                ], 200);
        } catch (\Exception $th) {
           DB::rollBack();
           return new JsonResponse(
            [
                'message' => 'Data Tidak Valid',
                'result' => $th->getMessage()
            ], 500);
        }
    }
    public function getjurnalpost(){
        $awal=request('tahun').'-'. request('bulan').'-01';
        $akhir=request('tahun').'-'. request('bulan').'-31';
        $data = Create_JurnalPosting::select(
            'jurnal_postingotom.notrans',
                    'jurnal_postingotom.tanggal',
                    'jurnal_postingotom.kegiatan',
                    'jurnal_postingotom.keterangan',
                    'jurnal_postingotom.kode',
                    'jurnal_postingotom.uraian',
                    'jurnal_postingotom.debit',
                    'jurnal_postingotom.kredit',
                    'jurnal_postingotom.verif')
        // ->where('jurnal_postingotom.verif', '=', null)
        ->where('jurnal_postingotom.verif', request('jenis'))
        ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        ->where(function($query){
            $query->when(request('q'), function($q){
                $q->where('notrans', 'like', '%'.request('q').'%')
                ->orWhere('tanggal', 'like', '%'.request('q').'%')
                ->orWhere('kegiatan', 'like', '%'.request('q').'%')
                ->orWhere('keterangan', 'like', '%'.request('q').'%')
                ->orWhere('debit', 'like', '%'.request('q').'%')
                ->orWhere('kredit', 'like', '%'.request('q').'%');
            });
        })
        ->get();
        return new JsonResponse($data);
    }
    public function verifjurnal(Request $request){
        $time = date('Y-m-d H:i:s');
        $data = Create_JurnalPosting::where('notrans', $request->notrans);
        // return $data;
        $data->update([
            'verif' => '1',
            'tglverif' => $time

    ]);
        return new JsonResponse (['message' => 'Data Berhasil di Verifikasi', 'notrans' => $request->notrans], 200);
    }
}
