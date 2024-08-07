<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiLS;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\FormatingHelper;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Siasik\TransaksiLS\NpdLS_heder;
use App\Models\Siasik\Master\Mapping_Bidang_Ptk_Kegiatan;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastrinciM;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Returpbfrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Support\Facades\Date;

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

    public function bastfarmasi(){
        // $pbf = Mpihakketiga::where('kode', request('kodepenerima'))->get();
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
        ->with('penerimaanrinci', function($rinci) {
            $rinci->select('penerimaan_r.nopenerimaan',
                            'penerimaan_r.kdobat',
                            'penerimaan_r.harga_netto',
                            'penerimaan_r.harga_netto_kecil',
                            'penerimaan_r.jml_all_penerimaan',
                            'penerimaan_r.subtotal')
                    ->with('masterobat', function ($rekening){
                        $rekening->select('new_masterobat.kd_obat',
                                        'new_masterobat.kode50',
                                        'new_masterobat.uraian50',
                                        'new_masterobat.kode108',
                                        'new_masterobat.uraian108');
                    });
        })
        // ->orderBy('tgl_bast', 'DESC')
        ->orderBy('nobast', 'asc')
        ->groupBy('nobast')
        ->paginate(request('per_page'));
        // ->get();
        return new JsonResponse($penerimaan);
    }
    public function simpannpd(Request $request)
    {
        $request->validate([
            'keterangan' => 'required|min:3',
            'pptk' => 'required',
        ]);

        $time = date('Y-m-d H:i:s');
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;

        $nomor = $request->nonpdls ?? self::buatnomor();

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
            ]
        );
        if (!$save){
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }
        return new JsonResponse(
            [
                'message' => 'Data Berhasil disimpan...!!!',
                'result' => $save
            ],
            200
        );
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
