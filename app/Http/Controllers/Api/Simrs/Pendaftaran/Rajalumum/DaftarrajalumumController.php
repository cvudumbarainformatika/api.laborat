<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Rajalumum;

use App\Helpers\BridgingbpjsHelper;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Sigarang\Transaksi\Retur\Retur;
use App\Models\Simrs\Master\Mcounter;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Pendaftaran\Karcispoli;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\TryCatch;

class DaftarrajalumumController extends Controller
{

    public static function simpanMpasien($request){
        $request->validate([
            'norm' => 'required|string|max:6|min:6',
            'tglmasuk' => 'required|date_format:Y-m-d H:i:s',
            'nik' => 'string|max:16|min:16',
            'tgllahir' => 'required|date_format:Y-m-d'
        ]);
        $masterpasien = Mpasien::updateOrCreate(['rs1' => $request->norm],
                [
                    'rs2' => $request->nama,
                    'rs3' => $request->sapaan,
                    'rs4' => $request->alamat,
                    'alamatdomisili' =>$request->alamatdomisili,
                    'rs5' => $request->kelurahan,
                    'kd_kel' =>$request->kodekelurahan,
                    'rs6' => $request->kecamatan,
                    'kd_kec' => $request->kodekecamatan,
                    'rs7' => $request->rt,
                    'rs8' => $request->rw,
                    'rs10' => $request->propinsi,
                    'kd_propinsi' => $request->kodepropinsi,
                    'rs11' => $request->kabupatenkota,
                    'kd_kota' =>$request->kodekabupatenkota,
                    'rs49' => $request->nik,
                    'rs37' => $request->templahir,
                    'rs16' => $request->tgllahir,
                    'rs17' => $request->kelamin,
                    'rs19' => $request->pendidikan,
                    'kd_kelamin' => $request->kodekelamin,
                    'rs22' => $request->agama,
                    'kd_agama' => $request->kodemapagama,
                    'rs39' => $request->suku,
                    'rs55' => $request->noteleponhp,
                    'bahasa' => $request->bahasa,
                    'noidentitaslain' => $request->nomoridentitaslain,
                    'namaibu' => $request->namaibukandung,
                    'kodepos' => $request->kodepos,
                    'kd_negara' => $request->negara,
                    'kd_rt_dom' => $request->rtdomisili,
                    'kd_rw_dom' => $request->rwdomisili,
                    'kd_kel_dom' => $request->kelurahandomisili,
                    'kd_kec_dom' => $request->kecamatandomisili,
                    'kd_kota_dom' => $request->kabupatenkotadomisili,
                    'kodeposdom' => $request->kodeposdomisili,
                    'kd_prov_dom' => $request->propinsidomisili,
                    'kd_negara_dom' => $request->negaradomisili,
                    'noteleponrumah' => $request->noteleponrumah,
                    'kd_pendidikan' => $request->kodependidikan,
                    'kd_pekerjaan' => $request->pekerjaan,
                    'flag_pernikahan' => $request->statuspernikahan,
                    'rs46' => $request->nokabpjs,
                    'rs40' => $request->barulama,
                    'gelardepan' => $request->gelardepan,
                    'gelarbelakang' => $request->gelarbelakang
                ]
            );
            return $masterpasien;
    }

    public static function simpankunjunganpoli($request)
    {
        $tglmasukx = Carbon::create($request->tglmasuk);
        $tglmasuk = $tglmasukx->toDateString();
        $cekpoli = KunjunganPoli::where('rs2', $request->norm)
        ->where('rs8', $request->kodepoli)
        ->whereDate('rs3', $tglmasuk)
        ->count();

        if($cekpoli > 0)
        {
            // return new JsonResponse(['message' => 'PASIEN SUDAH ADA DI HARI DAN POLI YANG SAMA'], 500);
            return false;
        }

        DB::select('call reg_rajal(@nomor)');
            $hcounter = DB::table('rs1')->select('rs13')->get();
            $wew = $hcounter[0]->rs13;
            $noreg = FormatingHelper::gennoreg($wew,'J');

            $input = new Request( [
                'noreg' => $noreg
            ]);

            $input->validate([
                'noreg' => 'required|unique:rs17,rs1'
            ]);

        //   $wew =  Validator::make($input, [
        //         'noreg' => 'unique:rs17,rs1'
        //     ]);

           $simpankunjunganpoli = KunjunganPoli::create([
                'rs1' => $input->noreg,
                'rs2' => $request->norm,
                'rs3' => $request->tglmasuk,
                'rs6' => $request->asalrujukan,
                'rs8' => $request->kodepoli,
                //'rs9' => $request->dpjp,
                'rs10' => 0,
                'rs11' => '',
                'rs12' => 0,
                'rs13' => 0,
                'rs14' => $request->sistembayar,
                'rs15' => $request->karcis,
                'rs18' => auth()->user()->pegawai_id,
                'rs20' => 'Pendaftaran',

            ]);
        return [
            'simpan'=>$simpankunjunganpoli?'':$simpankunjunganpoli,
            'input'=>$input,
            'masuk'=>$tglmasuk,
            'count'=>$cekpoli
        ];
    }

    public static function simpankarcis($request,$input)
    {
        $kode_biaya=explode('#',$request->kode_biaya);
        $nama_biaya=explode('#',$request->nama_biaya);
        $sarana=explode('#',$request->sarana);
        $pelayanan=explode('#',$request->pelayanan);

        $anu=[];
        foreach($kode_biaya as $key => $value)
        {
            // $xxx = new Karcispoli();
            // $xxx->kode_biaya = $value;
            // $xxx->nama_biaya = $nama_biaya[$key];
            // $xxx->sarana = $sarana[$key];
            // $xxx->pelayanan = $pelayanan[$key];

            // if()
            $kar=Karcispoli::firstOrCreate(
                [
                    'rs2' => $request->norm,
                    'rs4' => $request->tglmasuk,
                    'rs3' => $value.'#',
                ],
                [
                'rs1' => $input,
                // 'rs3' => $xxx->kode_biaya,
                'rs5' => 'D',
                 'rs6' => $nama_biaya[$key],
                'rs7' => $sarana[$key],
                'rs8' => $request->sistembayar,
                'rs10' => 'userenrty',
                // 'rs11' => $xxx->pelayanan,
                'rs11' => $pelayanan[$key],
                'rs12' => 'userentry',
                'rs13' => '1'
            ]);
            if($kar){
                array_push($anu,$kar);
            }
        }
        return $anu;
    }

    public function simpandaftar(Request $request)
    {
        try {
            //code...
            DB::beginTransaction();

            //-----------Masuk Transaksi--------------
           // $user = auth()->user(]);
           $masterpasien=$this->simpanMpasien($request);
           $simpankunjunganpoli=$this->simpankunjunganpoli($request);
           if($simpankunjunganpoli){
            $karcis=$this->simpankarcis($request,$simpankunjunganpoli['input']->noreg);
           }


            DB::commit();
            return new JsonResponse([
                'message' => 'DATA TERSIMPAN...!!!',
                'noreg' => $simpankunjunganpoli?$simpankunjunganpoli['input']->noreg:'gagal',
                'cek' => $simpankunjunganpoli?$simpankunjunganpoli['count']:'gagal',
                'masuk' => $simpankunjunganpoli?$simpankunjunganpoli['masuk']:'gagal',
                'hasil' => $simpankunjunganpoli?$simpankunjunganpoli['simpan']:'gagal',
                'karcis' => $karcis?$karcis:'gagal',
                'master' => $masterpasien,
            ],
                200);
        } catch (\Exception $th) {
            //throw $th;
            DB::rollBack();
            return response()->json(['Gagal tersimpan'=>$th],500);
        }

    }

    public function listpasienumum()
    {
        $tgldari = date('2023-01-10 00:00:00');
        $tglsampai = date('2023-01-10 23:59:59');
        $listpasienumum = KunjunganPoli::select('rs1','rs2','rs3','rs8','rs14')
        ->with(
            'masterpasien',
            'relmpoli:rs1,rs2',
            'msistembayar:rs1,rs2')
        ->whereBetween('rs3', [$tgldari, $tglsampai])
        ->where('rs14','=','UMUM')
        ->where('rs8','!=', 'POL014')
        ->paginate(request('per_page'));
        return new JsonResponse($listpasienumum) ;
    }

    public function createsep(Request $request)
    {

        $data =[
            "request"=>[
                "t_sep"=>[
                    "noKartu" => '0003519299158',
                    "tglSep" => '2023-06-05',
                    "ppkPelayanan" => '1327R001',
                    "jnsPelayanan" => '2',
                    "klsRawat"=>[
                        "klsRawatHak" => '3',
                        "klsRawatNaik" => '',
                        "pembiayaan" => '',
                        "penanggungJawab" => '',
                    ],
                    "noMR" => 'MR9835',
                    "rujukan"=>[
                        "asalRujukan"=>"2",
                        "tglRujukan"=>"2021-07-23",
                        "noRujukan"=>"RJKMR9835001",
                        "ppkRujukan"=>"1327R001"
                    ],
                    "catatan"=>"testinsert RJ",
                    "diagAwal"=>"E10",
                    "poli"=>[
                        "tujuan"=>"",
                        "eksekutif"=>"0"
                    ],
                    "cob"=>[
                        "cob"=>"0"
                     ],
                     "katarak"=>[
                        "katarak"=>"0"
                     ],
                     "jaminan"=>[
                        "lakaLantas"=>"0",
                        "noLP"=>"12345",
                        "penjamin"=>[
                           "tglKejadian"=>"",
                           "keterangan"=>"",
                           "suplesi"=>[
                              "suplesi"=>"0",
                              "noSepSuplesi"=>"",
                              "lokasiLaka"=>[
                                 "kdPropinsi"=>"",
                                 "kdKabupaten"=>"",
                                 "kdKecamatan"=>""
                              ]
                           ]
                        ]
                     ],
                     "tujuanKunj"=>"0",
                     "flagProcedure"=>"",
                     "kdPenunjang"=>"",
                     "assesmentPel"=>"",
                     "skdp"=>[
                        "noSurat"=>"",
                        "kodeDPJP"=>"31574"
                     ],
                     "dpjpLayan"=>"",
                     "noTelp"=>"081111111101",
                     "user"=>"Coba Ws"
                ]
            ]
        ];
        $createsep = BridgingbpjsHelper::post_url(
            'vclaim',
            '/SEP/2.0/insert', $data);
        return($createsep);
    }

}
