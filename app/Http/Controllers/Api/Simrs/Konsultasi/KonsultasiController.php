<?php

namespace App\Http\Controllers\Api\Simrs\Konsultasi;

use App\Events\NotifMessageEvent;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Konsultasi\Konsultasi;
use App\Models\Simrs\Master\Mhais;
use App\Models\Simrs\Master\Rstigapuluhtarif;
use App\Models\Simrs\Visite\Visite;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KonsultasiController extends Controller
{


    public function simpandata(Request $request)
    {

        $dokter = Petugas::where('kdpegsimrs', $request->kddokterkonsul)->where('aktif', 'AKTIF')->first();

        if (!$dokter) {
            return new JsonResponse(['message' => 'Maaf Dokter Tidak Terdaftar di simrs'], 500);
        }

        // $spesialis = strtoupper($dokter->statusspesialis) === 'SPESIALIS';


        // $tarifKonsul = self::cekTarip($spesialis, $request);
        // if (!$tarifKonsul) {
        //   return new JsonResponse(['message' => 'Maaf Ada error Server .... harap menghubungi IT'], 500);
        // }

        // return $tarifKonsul;



        $user = FormatingHelper::session_user();
        $tglInput = date('Y-m-d H:i:s');

        $data = null;
        if ($request->has('id')) {
            $data = Konsultasi::find($request->id);
        } else {
            $data = new Konsultasi();
        }

        $data->noreg = $request->noreg;
        $data->norm = $request->norm;
        $data->kddokterkonsul = $request->kddokterkonsul;
        $data->kduntuk = $request->kduntuk;
        $data->ketuntuk = $request->ketuntuk;
        $data->permintaan = $request->permintaan;
        $data->tgl_permintaan = $tglInput;
        $data->kdminta = $user['kodesimrs'] ?? '';
        $data->user = $user['kodesimrs'] ?? '';
        $data->kdruang = $request->kdruang ?? null;
        $data->user_jawab = null;
        $data->save();

        // simpan tarif konsultasi select * from rs140 where rs1='".trim($_GET['noreg'])."' and rs3='".trim($_GET['kodedokter'])."' and date(rs2)='".trim($_GET['tglx'])."' and rs6='".trim($_GET['flag_biaya'])."'"
        // $konsul = Visite::where('rs1', $request->noreg)
        // ->where('rs3', $request->kddokterkonsul)
        // ->whereDate('rs2', $request->tgljawab)
        // ->where('rs6', $request->flag_biaya)
        // ->get();

        return new JsonResponse(['message' => 'Data Berhasil Disimpan', 'result' => $data->load([
            'tarif:id,rs1,rs3,rs4,rs5,rs6,rs7,rs8,rs9,rs10',
            'nakesminta:kdpegsimrs,nama,kdgroupnakes,statusspesialis'
        ])], 200);
    }




    public function hapusdata(Request $request)
    {
        $cek = Konsultasi::find($request->id);
        if (!$cek) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 500);
        }

        $hapus = $cek->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }



    public function getdatarkd()
    {
        $user = FormatingHelper::session_user();
        $data = Konsultasi::selectRaw('id,noreg,norm,flag,kddokterkonsul,ketuntuk,permintaan,tgl_permintaan,jawaban,tgl_jawaban,kdminta,user,kdruang')
        ->where('kddokterkonsul', $user['kodesimrs'])
        ->where('flag', request('status'))
        ->with([
            'dokterkonsul' => function ($q) {
                $q->select('nama', 'kdpegsimrs', 'nip', 'nik', 'foto', 'aktif')
                    ->where('aktif', 'AKTIF');
            },
            'nakesminta' => function ($q) {
                $q->select('nama', 'kdpegsimrs', 'nip', 'nik', 'foto', 'aktif')
                    ->where('aktif', 'AKTIF');
            },
            'userinput' => function ($q) {
                $q->select('nama', 'kdpegsimrs', 'nip', 'nik', 'foto', 'aktif')
                    ->where('aktif', 'AKTIF');
            },
            'kunjunganranap' => function ($q) {
                $q->select(
                    'rs23.rs1',
                    'rs23.rs2',
                    'rs23.rs3',
                    'rs23.rs5',
                    'rs23.rs41 as statuspulang',
                    'rs15.rs2 as nama',
                    'rs23.rs19 as kodesistembayar', // ini untuk farmasi
                    'rs24.rs2 as ruangan',
                    'rs24.rs3 as kelas_ruangan',
                    'rs24.rs4 as kdgroup_ruangan',
                )
                    ->leftJoin('rs15', 'rs15.rs1', 'rs23.rs2')
                    ->leftjoin('rs24', 'rs24.rs1', 'rs23.rs5')
                    ->with([
                        'diagnosamedis' => function ($q) {
                            $q->with('masterdiagnosa')
                                ->where('rs13', '!=', 'POL014');
                        }
                    ])
                ;
            },
            'kunjunganpoli' => function ($q) {
                $q->select(
                    'rs17.rs1',
                    'rs17.rs2',
                    'rs17.rs3',
                    'rs17.rs8',
                    'rs17.rs19 as statuspulang',
                    'rs15.rs2 as nama',
                )
                    ->leftJoin('rs15', 'rs15.rs1', 'rs17.rs2')
                    ->where('rs17.rs8', '!=', 'POL014')
                ;
            },
            'kunjunganigd' => function ($q) {
                $q->select(
                    'rs17.rs1',
                    'rs17.rs2',
                    'rs17.rs3',
                    'rs17.rs8',
                    'rs17.rs19 as statuspulang',
                    'rs15.rs2 as nama',
                    'rs19.rs2 as ruangan'
                )
                    ->leftJoin('rs15', 'rs15.rs1', 'rs17.rs2')
                    ->leftJoin('rs19', 'rs19.rs1', 'rs17.rs8')
                    ->with([
                        'diagnosa' => function ($q) {
                            $q->with('masterdiagnosa')
                                ->where('rs13', '=', 'POL014');
                        }
                    ])
                    ->where('rs17.rs8', '=', 'POL014')
                ;
            },

        ])
        ->orderBy('id', 'desc')
        ->simplePaginate(request('perPage'));

        return response()->json($data);
    }

    public function updateFlag(Request $request)
    {
        $data = Konsultasi::find($request->id);
        $data->flag = '1';
        $data->save();
    }
    public function updateFlagAllRead(Request $request)
    {
        $akun = Petugas::find(auth()->user()->pegawai_id)->kdpegsimrs;

        $data = Konsultasi::where('kddokterkonsul', $akun)
                ->whereNull('flag')
                ->whereNull('jawaban')
                ->update(['flag'=> '1']);

        return response()->json(
            [
                'data'=> $data,
                'akun'=> $akun
            ]
        );
    }


    public function updateJawaban(Request $request)
    {

        // $user = FormatingHelper::session_user();

        $dokter = Petugas::find(auth()->user()->pegawai_id);

        $data = Konsultasi::find($request->id);

        // 
        

        if (!$data) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 500);
        }

        $tglJawab = $data->tgl_jawaban ?? null;
        $ranap = false;
        // $ranap = !($request->kdruang !== 'POL014' || $request->kdruang !== 'PEN005') ? false : true; // tambahan HD dari wawan
        $ranap = $request->kdruang !== 'POL014' && $request->kdruang !== 'PEN005'; // Perbaikan logika tak ubah wan ....


       


            $hari_ini = date('Y-m-d H:i:s');
            

             if ($tglJawab === null || $tglJawab === '0000-00-00 00:00:00' || $tglJawab === '') {
                // jika belum menjawab maka simpan tanggal harini
                $data->tgl_jawaban = $hari_ini;
            }

            

            // cari tarif dokter dan masukkan ke tarif jika dlm 1 hari belum ada data masuk

            // cek tarif
            $tarifKonsul = null;

            // jika yang jawab dokter
            if ($data->kddokterkonsul) {

                
                // jika nantinya HD ada tarif makan masuk ke tindakan, rs 73 seharga 80.000
                if ($ranap) {

                    // jika dokter spesialis
                    $spesialis = (strtoupper($dokter->statusspesialis) === 'SPESIALIS' || strtoupper($dokter->statusspesialis) === 'SUB SPESIALIS');
                    // jika bukan dari IGD dan HD
                    $tarifKonsul = self::cekTarip($spesialis, $request, $dokter);
                    // return $tarifKonsul;
                    if (!$tarifKonsul) {
                        return new JsonResponse(['message' => 'Maaf ... Ada Kesalahan Pada Tarif Konsul'], 500);
                    }

                   $tgl_konsul = $data->tgl_permintaan ?? Carbon::now();
                   $tgl_konsul = Carbon::parse($tgl_konsul)->format('Y-m-d'); 

                    //cek data tarif harini untuk dokter
                    $cekTarif = Visite::select('*')
                        ->where('rs1', $request->noreg)
                        ->where('rs3', $data->kddokterkonsul)
                        ->where('rs2', 'LIKE', '%' . $tgl_konsul . '%')
                        ->where('rs6', $tarifKonsul['flag_biaya'])
                        ->get();

                    // return new JsonResponse($cekTarif);
                    // jika yg minta dokter
                    // if ($request->kdgroupnakesminta === '1') {
                        // jika billing belum masuk
                        if (count($cekTarif) === 0) {
                            $masukTarif = Visite::create([
                                'rs1' => $request->noreg,
                                'rs2' => $tgl_konsul,
                                'rs3' => $data->kddokterkonsul ?? '',
                                'rs4' => $tarifKonsul['sarana'],
                                'rs5' => $tarifKonsul['pelayanan'],
                                'rs6' => $tarifKonsul['flag_biaya'],
                                'rs8' => $request->kdgroup_ruangan ?? '',
                                'rs9' => $request->kodesistembayar ?? ''
                            ]);
                            // ini baru
                            $data->rs140_id = $masukTarif ? $masukTarif->id ?? null : null;
                        }
                    // }
                    
                    // return new JsonResponse($cekTarif);
                    
                }
            }
        // }




        $data->flag = '2';

        $data->jawaban = $request->jawaban;
        // $data->kdruang = $request->kdruang;
        $data->user_jawab = $dokter->kdpegsimrs ?? null;
        $data->save();

        // return $data;

        // $lazy = Konsultasi::find($data->id)->with([
        //   'tarif:rs1,rs3,rs4,rs5,rs6,rs7,rs8,rs9,rs10',
        //   'nakesminta:kdpegsimrs,nama,kdgroupnakes,statusspesialis',
        // ]); 
        // $lazy=$data;
        $lazy = $data->load([
            'tarif:id,rs1,rs3,rs4,rs5,rs6,rs7,rs8,rs9,rs10',
            'nakesminta:kdpegsimrs,nama,kdgroupnakes,statusspesialis',
        ]);

        // $msg = [
        //   'data' => $lazy
        // ];
        // event(new NotifMessageEvent($msg, "konsultasi", auth()->user()));

        return new JsonResponse([
            'message' => 'Jawaban tersimpan',
            'result' => $lazy,
            'ranap?' => $ranap,
        ], 200);
    }

    public static function cekTarip($spesialis, $request, $pegawai)
    {
        $rs = null;

        // return  [
        //     'spesialis' => $spesialis,
        //     'request' => $request->kelas_ruangan,
        //     'pegawai' => $pegawai
        // ];

        // if ($spesialis) {
        //     // $rs = Rstigapuluhtarif::where('rs3', 'K5#')->orWhere('rs3', 'K6#')
        //     //     ->where('rs4', 'like', '%|' . $request->kdgroup_ruangan . '|%')
        //     //     ->where('rs5', 'like', '%|' . $request->kelas_ruangan . '|%')
        //     //     ->get();

        //     // $rs = Rstigapuluhtarif::where('rs3', 'K5#')
        //     //           ->where('rs4', 'like', '%|' . $request->kdgroup_ruangan . '|%')
        //     //           ->where('rs5', 'like', '%|' . $request->kelas_ruangan . '|%')
        //     //           ->first();

            

        //     if ($request->kelas_ruangan==="IC" || $request->kelas_ruangan==="ICC" || $request->kelas_ruangan==="NICU" ){
        //         if (strtoupper($pegawai->statusspesialis === 'SPESIALIS')) {
        //           # code...
        //           $rs = Rstigapuluhtarif::where('rs3', 'K6#')
        //               ->where('rs4', 'like', '%|' . $request->kdgroup_ruangan . '|%')
        //               ->where('rs5', 'like', '%|' . $request->kelas_ruangan . '|%')
        //               ->first();
        //       } else if (strtoupper($pegawai->statusspesialis === 'SUB SPESIALIS')) {
        //           $rs = Rstigapuluhtarif::where('rs3', 'K10#')
        //               ->where('rs4', 'like', '%|' . $request->kdgroup_ruangan . '|%')
        //               ->where('rs5', 'like', '%|' . $request->kelas_ruangan . '|%')
        //               ->first();

                
        //       }
        //     } else {
                

        //         if (strtoupper($pegawai->statusspesialis === 'SPESIALIS')) {
        //           # code...
        //           $rs = Rstigapuluhtarif::where('rs3', 'K5#')
        //               ->where('rs4', 'like', '%|' . $request->kdgroup_ruangan . '|%')
        //               ->where('rs5', 'like', '%|' . $request->kelas_ruangan . '|%')
        //               ->first();


        //         return response()->json([
        //         'rs1' => $rs]);
        //       } else if (strtoupper($pegawai->statusspesialis === 'SUB SPESIALIS')) {
        //           $rs = Rstigapuluhtarif::where('rs3', 'K11#')
        //               ->where('rs4', 'like', '%|' . $request->kdgroup_ruangan . '|%')
        //               ->where('rs5', 'like', '%|' . $request->kelas_ruangan . '|%')
        //               ->first();
        //       }

        //     }

            


        // } else {

        //     if ($request->kelas_ruangan==="IC" || $request->kelas_ruangan==="ICC" || $request->kelas_ruangan==="NICU" ){

        //         $rs = Rstigapuluhtarif::where('rs3', 'K8#')->orWhere('rs3', 'K8#')
        //         ->where('rs4', 'like', '%|' . $request->kdgroup_ruangan . '|%')
        //         ->where('rs5', 'like', '%|' . $request->kelas_ruangan . '|%')
        //         ->get();

        //     } else {

        //          $rs = Rstigapuluhtarif::where('rs3', 'K4#')->orWhere('rs3', 'K8#')
        //         ->where('rs4', 'like', '%|' . $request->kdgroup_ruangan . '|%')
        //         ->where('rs5', 'like', '%|' . $request->kelas_ruangan . '|%')
        //         ->get();
        //     }

           
        // }





        if ($spesialis) {
            $kelasIC = in_array($request->kelas_ruangan, ["IC", "ICC", "NICU"]);
            $status  = strtoupper($pegawai->statusspesialis);

            if ($kelasIC) {
                $kode = $status === 'SPESIALIS' ? 'K6#' : ($status === 'SUB SPESIALIS' ? 'K10#' : null);
            } else {
                $kode = $status === 'SPESIALIS' ? 'K5#' : ($status === 'SUB SPESIALIS' ? 'K11#' : null);
            }

            $rs = $kode
                ? Rstigapuluhtarif::where('rs3', $kode)
                    ->where('rs4', 'like', "%|{$request->kdgroup_ruangan}|%")
                    ->where('rs5', 'like', "%|{$request->kelas_ruangan}|%")
                    ->get()
                : null;

            
        } else {
            $kelasIC = in_array($request->kelas_ruangan, ["IC", "ICC", "NICU"]);
            $kode    = $kelasIC ? ['K8#'] : ['K4#', 'K8#'];

            $rs = Rstigapuluhtarif::whereIn('rs3', $kode)
                ->where('rs4', 'like', "%|{$request->kdgroup_ruangan}|%")
                ->where('rs5', 'like', "%|{$request->kelas_ruangan}|%")
                ->get();
        }

        // return response()->json(['percobaan' => $rs]);

        $rsx = collect($rs)->filter(function ($q) use ($request) {
            return Str::contains($q['rs5'], $request->kelas_ruangan) && Str::contains($q['rs4'], $request->kdgroup_ruangan);
        })->first();

        if (!$rsx) {
            return null;
        }

        // return response()->json(['percobaan222' => $rsx]);

        $sarana = 0;
        $pelayanan = 0;
        $flag_biaya = $rsx->rs3;

        $dokterRadiologi = $pegawai->profesi === 'J00113';
        $dokterPA = $pegawai->profesi === 'J00111';

        if ($dokterRadiologi || $dokterPA) {
            $sarana = 0;
            $pelayanan = 0;
        } else {

            if ($spesialis) {

                if ($request->kelas_ruangan === "3" || $request->kelas_ruangan === "IC" || $request->kelas_ruangan === "ICC" || $request->kelas_ruangan === "NICU" || $request->kelas_ruangan === "IN") {
                    $sarana = $rsx->rs6;
                    $pelayanan = $rsx->rs7;
                } else if ($request->kelas_ruangan == "2") {
                    $sarana = $rsx->rs8;
                    $pelayanan = $rsx->rs9;
                } else if ($request->kelas_ruangan == "1") {
                    $sarana = $rsx->rs10;
                    $pelayanan = $rsx->rs11;
                } else if ($request->kelas_ruangan == "Utama") {
                    $sarana = $rsx->rs12;
                    $pelayanan = $rsx->rs13;
                } else if ($request->kelas_ruangan == "VIP") {
                    $sarana = $rsx->rs14;
                    $pelayanan = $rsx->rs15;
                } else if ($request->kelas_ruangan == "VVIP") {
                    $sarana = $rsx->rs16;
                    $pelayanan = $rsx->rs17;
                } else if ($request->kelas_ruangan == "HCU") {


                    $hakKelas = $request->hak_kelas;
                    if ($hakKelas === '1') {
                        $sarana = $rsx->rs10;
                        $pelayanan = $rsx->rs11;
                    } else if ($hakKelas === '2') {
                        $sarana = $rsx->rs8;
                        $pelayanan = $rsx->rs9;
                    } else if ($hakKelas === '3') {
                        $sarana = $rsx->rs6;
                        $pelayanan = $rsx->rs7;
                    }
                } else if ($request->kelas_ruangan == "PS") {

                    $sarana = $rsx->pss;
                    $pelayanan = $rsx->psp;
                }
            } else {
                if ($request->kelas_ruangan === "3" || $request->kelas_ruangan === "IC" || $request->kelas_ruangan === "ICC" || $request->kelas_ruangan === "NICU" || $request->kelas_ruangan === "IN") {
                    $sarana = $rsx->rs6;
                    $pelayanan = $rsx->rs7;
                } else if ($request->kelas_ruangan === "2") {
                    $sarana = $rsx->rs8;
                    $pelayanan = $rsx->rs9;
                } else if ($request->kelas_ruangan === "1") {
                    $sarana = $rsx->rs10;
                    $pelayanan = $rsx->rs11;
                } else if ($request->kelas_ruangan === "Utama") {
                    $sarana = $rsx->rs12;
                    $pelayanan = $rsx->rs13;
                } else if ($request->kelas_ruangan === "VIP") {
                    $sarana = $rsx->rs14;
                    $pelayanan = $rsx->rs15;
                } else if ($request->kelas_ruangan === "VVIP") {
                    $sarana = $rsx->rs16;
                    $pelayanan = $rsx->rs17;
                } else if ($request->kelas_ruangan == "HCU") {


                    $hakKelas = $request->hak_kelas;
                    if ($hakKelas === '1') {
                        $sarana = $rsx->rs10;
                        $pelayanan = $rsx->rs11;
                    } else if ($hakKelas === '2') {
                        $sarana = $rsx->rs8;
                        $pelayanan = $rsx->rs9;
                    } else if ($hakKelas === '3') {
                        $sarana = $rsx->rs6;
                        $pelayanan = $rsx->rs7;
                    }
                } else if ($request->kelas_ruangan == "PS") {

                    $sarana = $rsx->pss;
                    $pelayanan = $rsx->psp;
                }
            }
        }


        $tarif = (int) $sarana + (int) $pelayanan;

        return [
            'flag_biaya' => $flag_biaya,
            'tarif' => $tarif,
            'sarana' => $sarana,
            'pelayanan' => $pelayanan
        ];
    }
}
