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
use App\Models\Siasik\Master\Mapping_Bidang_Ptk_Kegiatan;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastrinciM;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Returpbfrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use DateTime;

class NPD_LSController extends Controller
{
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
                                            'penerimaan_h.jenis_penerimaan',
                                            'penerimaan_h.kdpbf')
        ->where('kdpbf', request('kodepenerima'), function ($bast){
            $bast->whereIn('jenis_penerimaan', ['Pesanan']);
        })
        ->where('nobast', '!=', '')
        ->whereNotNull('tgl_bast')
        ->when(request('q'),function ($query) {
            $query->where('nobast', 'LIKE', '%' . request('q') . '%')
            ->orWhere('jumlah_bastx', 'LIKE', '%' . request('q') . '%');
        })
        ->with('rincianbast', function($rinci) use ($tahun) {
            $rinci->where('nobast', request('kodebast'))
                    ->select('bast_r.nobast',
                            'bast_r.id',
                            'bast_r.kdobat',
                            'bast_r.harga_net',
                            'bast_r.jumlah',
                            'bast_r.subtotal')
        // ->with('penerimaanrinci', function($rinci) use ($tahun) {
        //     $rinci->select('penerimaan_r.nopenerimaan',
        //                     'penerimaan_r.kdobat',
        //                     'penerimaan_r.harga_netto',
        //                     'penerimaan_r.harga_netto_kecil',
        //                     'penerimaan_r.jml_all_penerimaan',
        //                     'penerimaan_r.subtotal')
                    ->with('masterobat', function ($rekening) use ($tahun){
                        $rekening->select('new_masterobat.kd_obat',
                                        'new_masterobat.kode50',
                                        'new_masterobat.uraian50',
                                        'new_masterobat.kode108',
                                        'new_masterobat.uraian108')
                            ->with('pagu', function ($pagu) use ($tahun) {
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
                            });
                    });
        })
        // ->orderBy('tgl_bast', 'DESC')
        ->orderBy('nobast', 'asc')
        ->groupBy('nobast')
        ->paginate(request('per_page'));
        // ->get();

        $konsinyasi = BastKonsinyasi::select('bast_konsinyasis.notranskonsi',
                                            'bast_konsinyasis.nobast',
                                            'bast_konsinyasis.kdpbf',
                                            'bast_konsinyasis.tgl_bast',
                                            'bast_konsinyasis.jumlah_bastx',)
        ->where('kdpbf', request('kodepenerima'), function ($bast){
            $bast->where('nobast', '!=', '');
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
                            ->with('pagu', function ($pagu) use ($tahun) {
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
                                        ->with('realisasi', function ($realisasi) {
                                            $realisasi->select('npdls_rinci.idserahterima_rinci',
                                                                'npdls_rinci.nominalpembayaran')
                                            ->selectRaw('sum(nominalpembayaran) as total_realisasi');
                                        });
                            });
                    });
        })
        ->orderBy('nobast', 'asc')
        ->groupBy('nobast')
        ->paginate(request('per_page'));

        $bast = [
            'penerimaan' => $penerimaan,
            'konsinyasi' => $konsinyasi,
        ];

        return new JsonResponse($bast);
    }
    public function coba(){
        $tahun = date('Y');
        $konsinyasi = BastKonsinyasi::select('bast_konsinyasis.notranskonsi',
                                            'bast_konsinyasis.nobast',
                                            'bast_konsinyasis.kdpbf',
                                            'bast_konsinyasis.tgl_bast',
                                            'bast_konsinyasis.jumlah_bastx',)
        ->where('kdpbf', request('kodepenerima'), function ($bast){
            $bast->where('nobast', '!=', '');
        })
        ->whereNotNull('tgl_bast')
        ->when(request('q'),function ($query) {
            $query->where('nobast', 'LIKE', '%' . request('q') . '%')
            ->orWhere('jumlah_bastx', 'LIKE', '%' . request('q') . '%');
        })
        ->with('rinci', function($rinci) use ($tahun) {
            $rinci->where('notranskonsi', request('kodebast'))
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
                            ->with('pagu', function ($pagu) use ($tahun) {
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
                                        ->with('realisasi', function ($realisasi) {
                                            $realisasi->select('npdls_rinci.idserahterima_rinci',
                                                                'npdls_rinci.nominalpembayaran')
                                            ->selectRaw('sum(nominalpembayaran) as total_realisasi');
                                        });
                            });
                    });
        })
        ->orderBy('nobast', 'asc')
        ->groupBy('nobast')
        ->paginate(request('per_page'));
        return new JsonResponse($konsinyasi);
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
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;

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
                    ]);

                }
            return new JsonResponse(
                [
                    'message' => 'Data Berhasil disimpan...!!!',
                    'result' => $save
                ], 200);
        } catch (\Exception $er) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'Ada Kesalahan',
                'error' => $er
            ], 500);
        }
    }

    public static function buatnomor(){
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;

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
