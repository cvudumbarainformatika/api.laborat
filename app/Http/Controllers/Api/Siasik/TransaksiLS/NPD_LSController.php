<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiLS;

use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastKonsinyasi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\FormatingHelper;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Date;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Siasik\TransaksiLS\NpdLS_heder;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\Master\Akun_Kepmendg50;
use App\Models\Siasik\Master\Bagian;
use App\Models\Siasik\Master\Mapping_Bidang_Ptk_Kegiatan;
use App\Models\Siasik\TransaksiLS\NpdLS_rinci;
use App\Models\Siasik\TransaksiLS\NpkLS_rinci;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastrinciM;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Returpbfrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use DateTime;
use PhpOffice\PhpSpreadsheet\Calculation\TextData\Format;

class NPD_LSController extends Controller
{
    public function listnpdls()
    {
        // $user = auth()->user()->pegawai_id;
        // $pg= Pegawai::find($user);
        // $pegawai= $pg->kdpegsimrs;
        $tahunawal=date('Y');
        $tahun=date('Y');
        $npdls = NpdLS_heder::select(
            'npdls_heder.nonpdls',
                    'npdls_heder.nonpk',
                    'npdls_heder.nopencairan',
                    'npdls_heder.tglnpdls',
                    'npdls_heder.pptk',
                    'npdls_heder.kodepptk',
                    'npdls_heder.bidang',
                    'npdls_heder.kegiatanblud',
                    'npdls_heder.penerima',
                    'npdls_heder.kodepenerima',
                    'npdls_heder.bank',
                    'npdls_heder.rekening',
                    'npdls_heder.npwp',
                    'npdls_heder.keterangan',
                    'npdls_heder.noserahterima',
                    'npdls_heder.nopencairan',
                    'npdls_heder.userentry',
                    'npdls_heder.serahterimapekerjaan')
            // ->where('userentry', $pegawai)
            ->when(request('q'),function ($query) {
                $query
                ->where('nonpdls', 'LIKE', '%' . request('q') . '%')
                ->orWhere('tglnpdls', 'LIKE', '%' . request('q') . '%')
                ->orWhere('pptk', 'LIKE', '%' . request('q') . '%')
                ->orWhere('bidang', 'LIKE', '%' . request('q') . '%')
                ->orWhere('kegiatanblud', 'LIKE', '%' . request('q') . '%')
                ->orWhere('penerima', 'LIKE', '%' . request('q') . '%')
                ->orWhere('keterangan', 'LIKE', '%' . request('q') . '%')
                ->orWhere('nopencairan', 'LIKE', '%' . request('q') . '%')
                ;
            })->whereBetween('tglnpdls', [$tahunawal.'-01-01', $tahun.'-12-31'])
            ->with(['npdlsrinci'=> function($rinci){
                $rinci->select('npdls_rinci.nonpdls',
                            'npdls_rinci.nopenerimaan',
                            'npdls_rinci.koderek50',
                            'npdls_rinci.rincianbelanja',
                            'npdls_rinci.koderek108',
                            'npdls_rinci.uraian108',
                            'npdls_rinci.itembelanja',
                            'npdls_rinci.volumels',
                            'npdls_rinci.satuan',
                            'npdls_rinci.hargals',
                            'npdls_rinci.nominalpembayaran');
            },'npkrinci'=>function($cair) {
                $cair->select('nonpk','nonpdls')
                ->with('header', function($header){
                    $header->select('nonpk', 'tglpindahbuku');
                });
            }, 'pajak'])
            ->orderBy('tglnpdls', 'desc')
            ->get();
        return new JsonResponse($npdls);
    }
    // public function cetakPencairanNPD()
    // {
    //     $tahunawal=date('Y');
    //     $tahun=date('Y');
    //     $npdls = NpdLS_heder::select(
    //         'npdls_heder.nonpdls',
    //                 'npdls_heder.nonpk',
    //                 'npdls_heder.nopencairan',
    //                 'npdls_heder.tglnpdls',
    //                 'npdls_heder.pptk',
    //                 'npdls_heder.kodepptk',
    //                 'npdls_heder.bidang',
    //                 'npdls_heder.kegiatanblud',
    //                 'npdls_heder.penerima',
    //                 'npdls_heder.kodepenerima',
    //                 'npdls_heder.bank',
    //                 'npdls_heder.rekening',
    //                 'npdls_heder.npwp',
    //                 'npdls_heder.keterangan',
    //                 'npdls_heder.noserahterima',
    //                 'npdls_heder.nopencairan',
    //                 'npdls_heder.userentry',
    //                 'npdls_heder.serahterimapekerjaan')
    //         // ->where('userentry', $pegawai)

    //         ->where('nopencairan', '!=', '')
    //         ->whereBetween('tglnpdls', [$tahunawal.'-01-01', $tahun.'-12-31'])
    //         ->with(['npdlsrinci'=> function($rinci){
    //             $rinci->select('npdls_rinci.nonpdls',
    //                         'npdls_rinci.nopenerimaan',
    //                         'npdls_rinci.koderek50',
    //                         'npdls_rinci.rincianbelanja',
    //                         'npdls_rinci.koderek108',
    //                         'npdls_rinci.uraian108',
    //                         'npdls_rinci.itembelanja',
    //                         'npdls_rinci.volumels',
    //                         'npdls_rinci.satuan',
    //                         'npdls_rinci.hargals',
    //                         'npdls_rinci.nominalpembayaran');
    //         },'npkrinci'=>function($cair) {
    //             $cair->select('nonpk','nonpdls')
    //             ->with('header', function($header){
    //                 $header->select('nonpk', 'tglpindahbuku');
    //             });
    //         }])
    //         ->orderBy('tglnpdls', 'desc')
    //         ->get();
    //     return new JsonResponse($npdls);
    // }
    public function perusahaan()
    {
        $phk = Mpihakketiga::select('kode','nama','alamat','npwp','norek','bank','kodemapingrs','namasuplier')
        ->when(request('q'),function ($query) {
            $query->where('nama', 'LIKE', '%' . request('q') . '%');
        })
        ->get();

        return new JsonResponse($phk);
    }
    public function ptk()
    {
        $tahun = date('Y');
        // cari ptk kegiatan
        $cari = Mapping_Bidang_Ptk_Kegiatan::where('tahun', $tahun)->get();

        return new JsonResponse($cari);
    }

    // BELUM DIPAKE
    public function anggaran(){
        $tahun = date('Y');
        $anggaran = PergeseranPaguRinci::where('tgl', $tahun)
        // ->select('mappingpptkkegiatan.kegiatan','mappingpptkkegiatan.kodekegiatan')
        ->where('kodekegiatanblud', request('kodekegiatan'))
        ->where('pagu', '!=', '0')
        ->select('t_tampung.kodekegiatanblud',
                't_tampung.notrans',
                't_tampung.koderek50',
                't_tampung.koderek108',
                't_tampung.uraian108',
                't_tampung.uraian50',
                't_tampung.usulan',
                't_tampung.volume',
                't_tampung.satuan',
                't_tampung.harga',
                't_tampung.pagu',
                't_tampung.idpp')
                ->with(['jurnal','realisasi_spjpanjar'=> function ($realisasi) {
                    $realisasi->select('spjpanjar_rinci.iditembelanjanpd',
                                        'spjpanjar_rinci.jumlahbelanjapanjar');
                    },'realisasi'=> function ($realisasi) {
                    $realisasi->select('npdls_rinci.idserahterima_rinci',
                                        'npdls_rinci.nominalpembayaran')
                                        // ->sum('nominalpembayaran')
                                        // ->selectRaw('sum(nominalpembayaran) as total_realisasi')
                                        ;
                    },'contrapost'=> function ($realisasi) {
                    $realisasi->select('contrapost.idpp',
                                        'contrapost.nominalcontrapost');
                    }])
        // ->with('masterobat', function($sel){
        //     $sel->select('new_masterobat.kode108',
        //                 'new_masterobat.uraian108',
        //                 'new_masterobat.kd_obat')
        //         ->with('penerimaanrinci', function($data){
        //             $data->select('penerimaan_r.nopenerimaan',
        //             'penerimaan_r.kdobat',
        //             'penerimaan_r.harga_netto',
        //             'penerimaan_r.harga_netto_kecil',
        //             'penerimaan_r.jml_all_penerimaan',
        //             'penerimaan_r.subtotal');
        //         });
        // })
        // ->with('anggaran', function($pagu) use ($tahun){
        //     $pagu->where('tgl', $tahun)

        // })
        ->get();
        return new JsonResponse($anggaran);
    }
    public function bastfarmasi(){
        $tahun = date('Y');
        $penerimaan=PenerimaanHeder::select('penerimaan_h.nobast',
                                            'penerimaan_h.tgl_bast',
                                            'penerimaan_h.nopenerimaan',
                                            'penerimaan_h.jumlah_bastx',
                                            'penerimaan_h.subtotal_bast',
                                            'penerimaan_h.jenis_penerimaan',
                                            'penerimaan_h.kdpbf',
                                            'penerimaan_h.no_npd',)

        ->where('kdpbf', request('kodepenerima'), function ($bast){
            $bast->whereIn('jenis_penerimaan', ['Pesanan']);
        })
        ->where('nobast', '!=', '')
        ->where('no_npd', '=', '')
        ->whereNotNull('tgl_bast')
        ->when(request('q'),function ($query) {
            $query->where('nobast', 'LIKE', '%' . request('q') . '%')
            ->orWhere('jumlah_bastx', 'LIKE', '%' . request('q') . '%')
            ->orWhere('subtotal_bast', 'LIKE', '%' . request('q') . '%');
        })
        ->with('rincianbast', function($rinci) use ($tahun) {
            $rinci->where('nobast', request('kodebast'))
                    ->select('bast_r.nobast',
                            'bast_r.nopenerimaan',
                            'bast_r.id',
                            'bast_r.kdobat',
                            'bast_r.harga_net',
                            'bast_r.jumlah',
                            'bast_r.subtotal'
                            // DB::raw('(harga_net * jumlah) as totalobat')
                            )
                            // ->selectRaw('sum(harga_net * jumlah) as totalobat')
        // ->with('penerimaanrinci', function($rinci) use ($tahun) {
        //     $rinci->select('penerimaan_r.nopenerimaan',
        //                     'penerimaan_r.kdobat',
        //                     'penerimaan_r.harga_netto',
        //                     'penerimaan_r.harga_netto_kecil',
        //                     'penerimaan_r.jml_all_penerimaan',
        //                     'penerimaan_r.subtotal')
                    ->with('masterobat',function ($rekening) use ($tahun){
                        $rekening->select('new_masterobat.kd_obat',
                                        'new_masterobat.kode50',
                                        'new_masterobat.uraian50',
                                        'new_masterobat.kode108',
                                        'new_masterobat.uraian108')
                            ->with(['jurnal','pagu'=> function ($pagu) use ($tahun) {
                            $pagu->where('tgl', $tahun)
                                ->where('kodekegiatanblud', request('kodekegiatan'))
                                ->where('pagu', '!=', '0')
                                ->select('t_tampung.kodekegiatanblud',
                                        't_tampung.notrans',
                                        't_tampung.koderek50',
                                        't_tampung.koderek108',
                                        't_tampung.uraian50',
                                        't_tampung.usulan',
                                        't_tampung.volume',
                                        't_tampung.satuan',
                                        't_tampung.harga',
                                        't_tampung.pagu',
                                        't_tampung.idpp')
                                        ->with(['realisasi_spjpanjar'=> function ($realisasi) {
                                            $realisasi->select('spjpanjar_rinci.iditembelanjanpd',
                                                                'spjpanjar_rinci.jumlahbelanjapanjar');
                                            },'realisasi'=> function ($realisasi) {
                                            $realisasi->select('npdls_rinci.idserahterima_rinci',
                                                                'npdls_rinci.nominalpembayaran')
                                                                // ->sum('nominalpembayaran')
                                                                // ->selectRaw('sum(nominalpembayaran) as total_realisasi')
                                                                ;
                                            },'contrapost'=> function ($realisasi) {
                                            $realisasi->select('contrapost.idpp',
                                                                'contrapost.nominalcontrapost');
                                            }]);
                            }]);
                    });
        })
        // ->orderBy('tgl_bast', 'DESC')
        ->orderBy('nobast', 'asc')
        ->groupBy('nobast')
        // ->paginate(request('per_page'));
        ->get();

        $konsinyasi = BastKonsinyasi::select('bast_konsinyasis.notranskonsi',
                                            'bast_konsinyasis.nobast',
                                            'bast_konsinyasis.kdpbf',
                                            'bast_konsinyasis.tgl_bast',
                                            'bast_konsinyasis.jumlah_bastx',)
        ->where('kdpbf', request('kodepenerima'), function ($bast){
            $bast->where('nobast', '!=', '')
            ->where('no_npd', '=', '');
        })
        ->whereNotNull('tgl_bast')
        ->when(request('q'),function ($query) {
            $query->where('nobast', 'LIKE', '%' . request('q') . '%')
            ->orWhere('jumlah_bastx', 'LIKE', '%' . request('q') . '%');
        })
        ->with('rinci', function($rinci) use ($tahun) {
            $rinci
            ->where('notranskonsi', request('kodebast'))
                    ->select('detail_bast_konsinyasis.notranskonsi',
                            'detail_bast_konsinyasis.id',
                            'detail_bast_konsinyasis.kdobat',
                            'detail_bast_konsinyasis.harga_net',
                            'detail_bast_konsinyasis.jumlah',
                            'detail_bast_konsinyasis.subtotal')
        // ->with('penerimaanrinci', function($rinci) use ($tahun) {
        //     $rinci->select('penerimaan_r.nopenerimaan',
        //                     'penerimaan_r.kdobat',
        //                     'penerimaan_r.harga_netto',
        //                     'penerimaan_r.harga_netto_kecil',
        //                     'penerimaan_r.jml_all_penerimaan',
        //                     'penerimaan_r.subtotal')
                    ->with('obat', function ($rekening) use ($tahun){
                        $rekening->select('new_masterobat.kd_obat',
                                        'new_masterobat.kode50',
                                        'new_masterobat.uraian50',
                                        'new_masterobat.kode108',
                                        'new_masterobat.uraian108')
                            ->with(['jurnal','pagu' => function ($pagu) use ($tahun) {
                            $pagu->where('tgl', $tahun)
                                ->where('kodekegiatanblud', request('kodekegiatan'))
                                ->where('pagu', '!=', '0')
                                ->select('t_tampung.kodekegiatanblud',
                                        't_tampung.notrans',
                                        't_tampung.koderek50',
                                        't_tampung.koderek108',
                                        't_tampung.uraian50',
                                        't_tampung.usulan',
                                        't_tampung.volume',
                                        't_tampung.satuan',
                                        't_tampung.harga',
                                        't_tampung.pagu',
                                        't_tampung.idpp')
                                        ->with(['realisasi_spjpanjar'=> function ($realisasi) {
                                            $realisasi->select('spjpanjar_rinci.iditembelanjanpd',
                                                                'spjpanjar_rinci.jumlahbelanjapanjar');
                                            },'realisasi'=> function ($realisasi) {
                                            $realisasi->select('npdls_rinci.idserahterima_rinci',
                                                                'npdls_rinci.nominalpembayaran')
                                                                // ->sum('nominalpembayaran')
                                                                // ->selectRaw('sum(nominalpembayaran) as total_realisasi')
                                                                ;
                                            },'contrapost'=> function ($realisasi) {
                                            $realisasi->select('contrapost.idpp',
                                                                'contrapost.nominalcontrapost');
                                            }]);
                            }]);
                    });
        })
        ->orderBy('nobast', 'asc')
        ->groupBy('nobast')
        // ->paginate(request('per_page'));
        ->get();

        $bast = [
            'penerimaan' => $penerimaan,
            'konsinyasi' => $konsinyasi,
        ];

        return new JsonResponse($bast);
    }
    public function coba(){
        // $nip = '196611251996032003';
        $akun=NpdLS_heder::all();
        $filter = $akun->filter(function($kode){
            return $kode->nip == true;
        });
        $pegawai=Pegawai::with(['npd_heder'=>function($x) use ($filter){
            $x->where('nip',$filter);
        }])->get();

        return new JsonResponse($pegawai);
    }
    public function simpannpd(Request $request)
    {
        $this->validate($request,[
            'keterangan' => 'required|min:3',
            'pptk' => 'required',
            'tglnpdls' => 'required',
            // 'rincianbelanja' => 'required',
            // 'itembelanja' => 'required'
        ]);

        $time = date('Y-m-d H:i:s');
        // $user = auth()->user()->pegawai_id;
        // $pg= Pegawai::find($user);
        // $pegawai= $pg->kdpegsimrs;

        $nomor = $request->nonpdls ?? self::buatnomor();

        try {
            DB::beginTransaction();
            $save = NpdLS_heder::updateOrCreate(
                [
                    'nonpdls' => $nomor,
                ],
                [
                    'tglnpdls'=>$request->tglnpdls ?? '',
                    'kodepptk'=>$request->kodepptk ?? '',
                    'pptk'=>$request->pptk ?? '',
                    'serahterimapekerjaan'=>$request->serahterimapekerjaan ?? '',
                    'triwulan'=>$request->triwulan ?? '',
                    'program'=>'PROGRAM PENUNJANG URUSAN PEMERINTAH DAERAH KABUPATEN/KOTA',
                    'nokontrak'=>$request->nokontrak ?? '',
                    'kegiatan'=>'PELAYANAN DAN PENUNJANG PELAYANAN BLUD',
                    'kodekegiatanblud'=>$request->kodekegiatanblud ?? '',
                    'kegiatanblud'=>$request->kegiatanblud ?? '',
                    'kodepenerima'=>$request->kodepenerima ?? '',
                    'penerima'=>$request->penerima ?? '',
                    'bank'=>$request->bank ?? '',
                    'rekening'=>$request->rekening ?? '',
                    'npwp'=>$request->npwp ?? '',
                    'kodebidang'=>$request->kodebidang ?? '',
                    'bidang'=>$request->bidang ?? '',
                    'keterangan'=>$request->keterangan ?? '',
                    'biayatransfer'=>$request->biayatransfer ?? '',
                    'tglentry'=>$time ?? '',
                    'userentry'=>$pegawai ?? '',
                    'noserahterima'=>$request->noserahterima ?? '',
                    'kunci'=>'1'
                ]);
                $penerimaans = [];
            foreach ($request->rincians as $rinci){

                $save->npdlsrinci()->create(

                    [
                        'nonpdls' => $save->nonpdls,
                    // ],
                    // [
                        'koderek50'=>$rinci['koderek50'] ?? '',
                        'rincianbelanja'=>$rinci['rincianbelanja'] ?? '',
                        'koderek108'=>$rinci['koderek108'] ?? '',
                        'uraian108'=>$rinci['uraian108'] ?? '',
                        'itembelanja'=>$rinci['itembelanja'] ?? '',
                        'nopenerimaan'=>$rinci['nopenerimaan'] ?? '',
                        'idserahterima_rinci'=>$rinci['idserahterima_rinci'] ?? '',
                        'tglentry'=>$time ?? '',
                        'userentry'=>$pegawai ?? '',
                        'volume'=>$rinci['volume'] ?? '',
                        'satuan'=>$rinci['satuan'] ?? '',
                        'harga'=>$rinci['harga'] ?? '',
                        'total'=>$rinci['total'] ?? '',
                        'volumels'=>$rinci['volumels'] ?? '',
                        'hargals'=>$rinci['hargals'] ?? '',
                        'totalls'=>$rinci['totalls'] ?? '',
                        'nominalpembayaran'=>$rinci['nominalpembayaran'] ?? '',
                        'kode_lo'=>$rinci['kode_lo'] ?? '',
                        'uraian_lo'=>$rinci['uraian_lo'] ?? '',
                        'kode_neraca1'=>$rinci['kode_neraca1'] ?? '',
                        'uraian_neraca1'=>$rinci['uraian_neraca1'] ?? '',
                        'kode_neraca2'=>$rinci['kode_neraca2'] ?? '',
                        'uraian_neraca2'=>$rinci['uraian_neraca2'] ?? '',
                        'kode_lpsal'=>$rinci['kode_lpsal'] ?? '',
                        'uraian_lpsal'=>$rinci['uraian_lpsal'] ?? '',
                        'kode_lak'=>$rinci['kode_lak'] ?? '',
                        'uraian_lak'=>$rinci['uraian_lak'] ?? '',
                    ]);
                    //request nomer BAST
                    $penerimaans[]=$rinci['nopenerimaan'];
                }
                // update penerimaan atas nomer BAST FARMASI
                PenerimaanHeder::whereIn('nobast', $penerimaans)->update(['no_npd' => $save->nonpdls]);
                BastKonsinyasi::whereIn('nobast', $penerimaans)->update(['no_npd' => $save->nonpdls]);
            //     $data = PenerimaanHeder::where('nobast',['nopenerimaan'])->get();
            //     if ($data) {
            //         $data->update([
            //             'no_npd' => $save->nonpdls,
            //         ]);
            //         $ow[] = $data;
            //     }if (!$data) {
            //         return new JsonResponse(['message' => 'Gagal, Nomor BAST Tidak ditemukan'], 410);
            // }
            return new JsonResponse(
                [
                    'message' => 'Data Berhasil disimpan...!!!',
                    'result' => $save,
                    'penerimaans' => $penerimaans
                ], 200);
        } catch (\Exception $er) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'Ada Kesalahan',
                'error' => $er
            ], 500);
        }
    }
    public function deleterinci(Request $request)
    {
        $header = NpdLS_heder::
        where('nonpdls', $request->nonpdls)
        ->where('kunci', '!=', '')
        ->get();
        if(count($header) > 0){
            return new JsonResponse(['message' => 'NPD Masih Dikunci'], 500);
        }
        $findrinci = NpdLS_rinci::where( 'nopenerimaan',$request->nopenerimaan)->first();
        if (!$findrinci){
            return new JsonResponse( $findrinci, 200);
        }
            $findrinci->delete();

        $rinciAll = NpdLS_rinci::where('nonpdls', $request->nonpdls)->get();
        if(count($rinciAll) === 0){
            $header = NpdLS_heder::where('nonpdls', $request->nonpdls)->first();
            $header->delete();
        }
        return new JsonResponse([
            'message' => 'Data Berhasil dihapus'
        ]);
        // $rinci = NpdLS_rinci::where('nonpdls', $request->nonpdls)->get();
        // $header = NpdLS_heder::where('nonpdls', $request->nonpdls)
        // ->where('kunci', '')
        // ->get();
        // if(count($header) <= 0){
        //     return new JsonResponse([
        //         'message' => 'Gagal dihapus, Npd Masih Terkunci'
        //     ], 410);
        // }
        // if (count($header) > 0){
        //     $header->delete();
        // }
        // if (count($rinci) > 0){
        //     $rinci->delete();
        // }
        // $data = [
        //     'message' => 'Data sudah dihapus',
        //     'rinci' => $rinci,
        //     'header' => $header,
        //     'req' => $request->all(),
        // ];
        // $delete = $data->delete();
        // if(!$delete){
        //     return new JsonResponse(['message' => 'Data Gagal Dihapus'], 410);
        // }
        // if (count($rinci) === 1){
        //     $header = NpdLS_heder::find($data->nonpdls);
        //     $header->delete();
        //     return new JsonResponse(['message'=>'Data Header dan detail telah dihapus'], 200);
        // }
        // return new JsonResponse($data, 200);
    }
    public static function buatnomor(){
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;
        if($pegawai === ''){
            $pegawai = "RSUD";
        }else{
            $pegawai= $pg->kdpegsimrs;
        }
        // $x= $pg->bagian;
        // $bag=Bagian::select('kodebagian')->where('kodebagian', $x)->get();
        // $pegawai=$bag->kodebagian;

        $bidang = Mapping_Bidang_Ptk_Kegiatan::select('alias')->where('kodekegiatan', request('kodekegiatan'))->get();
        $huruf = ('NPD-LS');
        // $no = ('4.02.0.00.0.00.01.0000');
        date_default_timezone_set('Asia/Jakarta');
        // $tgl = date('Y/m/d');
        $rom = array('','I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII');
        $thn = date('Y');
        // $time = date('mis');
        // $nomer=Transaksi::latest();
        $cek = NpdLS_heder::count();
        if ($cek == null){
            $urut = "00001";
            $sambung = $urut.'/'.$rom[date('n')].'/'.strtoupper($pegawai).'/'.strtoupper($huruf).'/'.$thn;
        }
        else{
            $ambil=NpdLS_heder::all()->last();
            $urut = (int)substr($ambil->nonpdls, 0, 5) + 1;
            //cara menyambungkan antara tgl dn kata dihubungkan tnda .
            // $urut = "000" . $urut;
            if(strlen($urut) == 1){
                $urut = "0000" . $urut;
            }
            else if(strlen($urut) == 2){
                $urut = "000" . $urut;
            }
            else if(strlen($urut) == 3){
                $urut = "00" . $urut;
            }
            else if(strlen($urut) == 4){
                $urut = "0" . $urut;
            }
            else {
                $urut = (int)$urut;
            }
            $sambung = $urut.'/'.$rom[date('n')].'/'.strtoupper($pegawai).'/'.strtoupper($huruf).'/'.$thn;
        }

        return $sambung;
    }
    // public static function getQuartersBetween($start_ts, $end_ts)
    // {
    //     $quarters = [];
    //     $months_per_year = [];
    //     $years = self::getYearsBetween($start_ts, $end_ts);
    //     $months = self::getMonthsBetween($start_ts, $end_ts);

    //     foreach ($years as $year) {
    //         foreach ($months as $month) {
    //             if ($year->format('Y') == $month->format('Y')) {
    //                 $months_per_year[$year->format('Y')][] = $month;
    //             }
    //         }
    //     }

    //     foreach ($months_per_year as $year => $months) {
    //         $january = new Date('01-01-' . $year);
    //         $march = new Date('01-03-' . $year);
    //         $april = new Date('01-04-' . $year);
    //         $june = new Date('01-06-' . $year);
    //         $july = new Date('01-07-' . $year);
    //         $september = new Date('01-09-' . $year);
    //         $october = new Date('01-10-' . $year);
    //         $december = new Date('01-12-' . $year);

    //         if (in_array($january, $months) && in_array($march, $months)) {
    //             $quarter_per_year['label'] = 'T1 / ' . $year;
    //             $quarter_per_year['start_day'] = $january->startOfMonth();
    //             $quarter_per_year['end_day'] = $march->endOfMonth()->endOfDay();
    //             array_push($quarters, $quarter_per_year);
    //         }

    //         if (in_array($april, $months) && in_array($june, $months)) {
    //             $quarter_per_year['label'] = 'T2 / ' . $year;
    //             $quarter_per_year['start_day'] = $april->startOfMonth();
    //             $quarter_per_year['end_day'] = $june->endOfMonth()->endOfDay();
    //             array_push($quarters, $quarter_per_year);
    //         }

    //         if (in_array($july, $months) && in_array($september, $months)) {
    //             $quarter_per_year['label'] = 'T3 / ' . $year;
    //             $quarter_per_year['start_day'] = $july->startOfMonth();
    //             $quarter_per_year['end_day'] = $september->endOfMonth()->endOfDay();
    //             array_push($quarters, $quarter_per_year);
    //         }

    //         if (in_array($october, $months) && in_array($december, $months)) {
    //             $quarter_per_year['label'] = 'T4 / ' . $year;
    //             $quarter_per_year['start_day'] = $october->startOfMonth();
    //             $quarter_per_year['end_day'] = $december->endOfMonth()->endOfDay();
    //             array_push($quarters, $quarter_per_year);
    //         }
    //     }

    //     return $quarters;
    // }

}
