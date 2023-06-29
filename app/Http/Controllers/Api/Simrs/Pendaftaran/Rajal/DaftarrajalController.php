<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal;

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

class DaftarrajalController extends Controller
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

    public function daftarkunjunganpasienbpjs()
    {
        if(!request('tgl'))
        {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        }else{
            $tgl = request('tgl').' 00:00:00';
            $tglx = request('tgl').' 23:59:59';
        }
        $daftarkunjunganpasienbpjs = KunjunganPoli::select(
            'rs1 as noreg',
            'rs2',
            'rs3',
            'rs8',
            'rs9',
            'rs14'
        )
        ->with(['masterpasien' => function($q)
            {
                $q->select([
                    'rs1',
                    DB::raw('concat(rs3," ",gelardepan," ",rs2," ",gelarbelakang) as nama'),
                    DB::raw('concat(rs4," KEL ",rs5," RT ",rs7," RW ",rs8," ",rs6," ",rs11," ",rs10) as alamat'),
                    'rs16 as tgllahir',
                    'rs17 as kelamin',
                    'rs19 as pendidikan',
                    'rs22 as agama',
                    'rs37 as templahir',
                    'rs39 as suku',
                    'rs40 as jenispasien',
                    'rs46 as noka',
                    'rs49 as nktp',
                    'rs55 as nohp'
                ]);
            }
        ])->with([
            'msistembayar:rs1,rs2 as sistembayar,rs9 as groupsistembayar',
            'relmpoli:rs1,rs2 as namapoli,rs4',
            'dokter:rs1,rs2 as dokter',
            'seprajal:rs1,rs8 as sep'
        ])
        ->whereBetween('rs17.rs3', [$tgl, $tglx])
        ->where('rs17.rs8','!=', 'POL014')
        ->where('rs17.rs14','Like', '%BPJS%')
        ->paginate(10);
        return new JsonResponse($daftarkunjunganpasienbpjs);
    }

}
