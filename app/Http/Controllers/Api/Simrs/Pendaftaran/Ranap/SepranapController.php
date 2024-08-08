<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Ranap;

use App\Helpers\BridgingbpjsHelper;
use App\Helpers\DateHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use App\Models\Simrs\Pendaftaran\Ranap\Sepranap;
use App\Models\Simrs\Ranap\BpjsSpri;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SepranapController extends Controller
{
    public function sepranap()
    {
        $carisepranap = Sepranap::sepranap()->filter(request('noka'))->get();
        return new JsonResponse(['message' => 'OK', $carisepranap], 200);
    }

    public function getRujukanBridgingByNoka(Request $request)
    {
        $request->validate([
            'noka' => 'required'
        ]);

        $cariRujukan = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/Peserta/' . $request->noka);

        return new JsonResponse($cariRujukan, 200);
    }
    public function getPpkRujukan(Request $request)
    {
        $request->validate([
            'param' => 'required',
            'jnsFaskes' => 'required'
        ]);

        $cariRujukan = BridgingbpjsHelper::get_url('vclaim', 'referensi/faskes/' . $request->param.'/'.$request->jnsFaskes);

        return new JsonResponse($cariRujukan, 200);
    }
    public function getDiagnosaBpjs(Request $request)
    {
        $request->validate([
            'param' => 'required',
        ]);

        $data = BridgingbpjsHelper::get_url('vclaim', 'referensi/diagnosa/' . $request->param);

        return new JsonResponse($data, 200);
    }
    public function getPropinsiBpjs(Request $request)
    {
        // $request->validate([
        //     'param' => 'required',
        // ]);

        $data = BridgingbpjsHelper::get_url('vclaim', 'referensi/propinsi');

        return new JsonResponse($data, 200);
    }
    public function getKabupatenBpjs(Request $request)
    {
        $request->validate([
            'param' => 'required',
        ]);

        $data = BridgingbpjsHelper::get_url('vclaim', 'referensi/kabupaten/propinsi/' . $request->param);

        return new JsonResponse($data, 200);
    }
    public function getKecamatanBpjs(Request $request)
    {
        $request->validate([
            'param' => 'required',
        ]);

        $data = BridgingbpjsHelper::get_url('vclaim', 'referensi/kecamatan/kabupaten/' . $request->param);

        return new JsonResponse($data, 200);
    }
    public function getDpjpBpjs(Request $request)
    {
        $request->validate([
            'jnsPelayanan' => 'required',
            'tglPelayanan' => 'required',
            'kodeSpesialis'=>'required'
        ]);

        $data = BridgingbpjsHelper::get_url('vclaim', 'referensi/dokter/pelayanan/' . $request->jnsPelayanan. '/tglPelayanan/' . $request->poli.'/Spesialis/'.$request->kodeSpesialis);

        return new JsonResponse($data, 200);
    }

    public function getListRujukanPeserta(Request $request)
    {
       $pcare = self::rujukanPcare($request);
       $rs=self::rujukanRs($request);
       return new JsonResponse(['pcare'=>$pcare,'rs'=>$rs], 200);
    }

    public static function rujukanPcare($request){

        $request->validate([
            'noka'=>'required',
        ]);
        $data = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/List/Peserta/' . $request->noka);
        $list=[];
        if ($data['metadata']['code'] == '200') {
            $list = $data['result'];
        }

        return $list;
    }
    public static function rujukanRs($request){

        $request->validate([
            'noka'=>'required',
        ]);
        $data = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/RS/List/Peserta/' . $request->noka);
        $list=[];
        if ($data['metadata']['code'] == '200') {
            $list = $data['result'];
        }

        return $list;
    }

    public function getListSpri(Request $request)
    {
        $request->validate([
            'norm'=> 'required',
        ]);

        $data = BpjsSpri::select('noreg','norm','noSep','kodeDokter','namaDokter','tglRencanaKontrol','noSuratKontrol','noKartu','nama',)
        ->where('norm', $request->norm)->whereNull('batal')
        ->orderBy('created_at', 'desc')->get();
        return new JsonResponse($data, 200);
    }


    public function getSuplesi(Request $request)
    {
        $request->validate([
            'noka'=>'required',
        ]);
        $tglPelayanan = Carbon::now()->toDateString();
        $data = BridgingbpjsHelper::get_url('vclaim', 'sep/JasaRaharja/Suplesi/' . $request->noka. '/tglPelayanan/' . $tglPelayanan);
        $list=[];
        if ($data['metadata']['code'] == '200') {
            $list = $data['result'];
        }

        return $list;
    }

    public function getSepFromBpjs(Request $request)
    {
        $request->validate([
            'noSep'=> 'required',
        ]);

        $data = BridgingbpjsHelper::get_url('vclaim', 'SEP/' . $request->noSep);
        return new JsonResponse($data);
    }


    public function getNorujukanInternal(Request $request)
    {
        DB::select('call generetenorujukan(@nomor)');
        $hcounter = DB::table('rs1')->select('rs285')->first();
        $no = 0;
        $has=null;
        $num = date("y").date("m").date("d").'00000R';
        if ($hcounter) {
			$x=$hcounter->rs285;
            $no = $x+1;

            $panjang = strlen($no);
            for($i=1;$i<=4-$panjang;$i++){$has=$has."0";}
            $num = date("y").date("m").date("d").$has.$no."R";
        }
        return new JsonResponse($num);
    }



    public function create_sep_ranap(Request $request)
    {
        
        $data = [
            "request" => [
                "t_sep" => [
                    "noKartu" => $request->noKartu ? $request->noKartu : "",
                    "tglSep" => $request->tglSep ?? Carbon::now()->toDateString(),
                    "ppkPelayanan" => "1327R001",
                    "jnsPelayanan" => $request->jnsPelayanan ?? "1", //1. Rawat Inap, 2. Rawat Jalan
                    "klsRawat" => [
                        "klsRawatHak" => $request->klsRawat['klsRawatHak'] ?? '',
                        "klsRawatNaik" => $request->klsRawat['klsRawatNaik'] ?? '',
                        "pembiayaan" => $request->klsRawat['pembiayaan'] ?? '',
                        "penanggungJawab" => $request->klsRawat['penanggungJawab'] ?? '',
                    ],
                    "noMR" => $request->noMR ?? "",
                    "rujukan" => [
                        "asalRujukan" => $request->rujukan['asalRujukan'] ?? '',
                        "tglRujukan" => $request->rujukan['tglRujukan'] ?? '',
                        "noRujukan" => $request->rujukan['noRujukan'] ?? '',
                        "ppkRujukan" => $request->rujukan['ppkRujukan'] ?? ''
                    ],
                    "catatan" => $request->catatan ?? '-',
                    "diagAwal" => $request->diagAwal ?? '',
                    "poli" => ["tujuan" => $request->poli['tujuan'] ?? '', "eksekutif" => $request->poli['eksekutif'] ?? '0'],
                    "cob" => [
                        "cob" => $request->cob['cob'] ?? '0',
                    ],
                    "katarak" => [
                        "katarak" => $request->katarak['katarak'] ?? '0',
                    ],
                    "jaminan" => [
                        "lakaLantas" => $request->jaminan['lakaLantas'] ?? '0',
                        "noLP" => $request->jaminan['noLP'] ?? '',
                        "penjamin" => [
                            "tglKejadian" => $request->jaminan['penjamin']['tglKejadian'] ?? '',
                            "keterangan" => $request->jaminan['penjamin']['keterangan'] ?? '',
                            "suplesi" => [
                                "suplesi" => $request->jaminan['penjamin']['suplesi']['suplesi'] ?? '0',
                                "noSepSuplesi" => $request->jaminan['penjamin']['suplesi']['noSepSuplesi'] ?? '',
                                "lokasiLaka" => [
                                    "kdPropinsi" => $request->jaminan['penjamin']['suplesi']['lokasiLaka']['kdPropinsi'] ?? '',
                                    "kdKabupaten" => $request->jaminan['penjamin']['suplesi']['lokasiLaka']['kdKabupaten'] ?? '',
                                    "kdKecamatan" => $request->jaminan['penjamin']['suplesi']['lokasiLaka']['kdKecamatan'] ?? '',
                                ],
                            ],
                        ],
                    ],
                    "tujuanKunj" => $request->tujuanKunj ?? '0',
                    "flagProcedure" => $request->flagProcedure ?? '0',
                    "kdPenunjang" => $request->kdPenunjang ?? '',
                    "assesmentPel" => $request->assesmentPel ?? '',
                    "skdp" => [
                        "noSurat" => $request->skdp['noSurat'] ?? '', // ini dari SPRI
                        "kodeDPJP" => $request->skdp['kodeDPJP'] ?? '',
                    ],
                    "dpjpLayan" => $request->dpjpLayan ?? '', // untuk RANAP dikosongi
                    "noTelp" => $request->noTelp ?? '',
                    "user" => $request->user ?? '-',
                ],
            ],
        ];

        // return new JsonResponse($data);

        $tgltobpjshttpres = DateHelper::getDateTime();
        $createsep = BridgingbpjsHelper::post_url(
            'vclaim',
            'SEP/2.0/insert',
            $data
        );

        Bpjs_http_respon::create(
            [
                'method' => 'POST',
                'noreg' => $request->noreg === null ? '' : $request->noreg,
                'request' => $data,
                'respon' => $createsep,
                'url' => '/SEP/2.0/insert',
                'tgl' => $tgltobpjshttpres
            ]
        );

        // simpan ke rs227

        $bpjs = $createsep['metadata']['code'];
        if ($bpjs === 200 || $bpjs === '200') {
            $sep = $createsep['response']['sep'];
            $nosep = $sep->noSep;
            Sepranap::firstOrCreate(
                ['rs1' => $request->noreg],
                [
                    'rs2' => $request->noMR ?? "",
                    'rs3' => $request->sepRanap['ruang'] ?? "",
                    'rs4' => $request->sepRanap['kodesistembayar'] ?? "",
                    'rs5' => $request->rujukan['noRujukan'] ?? '',
                    'rs6' => $request->rujukan['tglRujukan'] ?? '',
                    'rs7' => $request->sepRanap['diagnosa'] ?? '',
                    'rs8'=> $nosep,
                    'rs9'=> $request->catatan ?? '-',
                    'rs10'=> $request->rujukan['ppkRujukan'] ?? '',
                    'rs11' =>$request->sepRanap['jenispeserta'] ?? '',
                    'rs12'=> $tgltobpjshttpres ?? '',
                    'rs13'=> $request->noKartu ?? '',
                    'rs14' => $request->sepRananp['nama'],
                    'rs15' => $request->sepRananp['tglLahir'],
                    'rs16' => $request->sepRananp['jeniskelamin']==='Laki-Laki' ? 'L' : 'P',
                    'rs17' => 'Rawat Inap',
                    'rs18'=> $request->sepRananp['hakKelas'] ?? '',
                    'rs19'=> '1',
                    'laka'=> $request->jaminan['lakaLantas'] ?? '0',
                    'lokasilaka' => $request->jaminan['penjamin']['suplesi']['lokasiLaka']['kdKabupaten'] ?? '',
                    'penjaminlaka' => $request->jaminan['penjamin']['suplesi']['noSepSuplesi'],
                    'users' => auth()->user()->pegawai_id,
                    'notelepon'=> $request->noTelp,
                    'tgl_entery' => $tgltobpjshttpres,
                    'namaasuransicob'=> $request->sepRanap['namaAsuransiCob'],
                    'noDpjp'=> $request->skdp['kodeDPJP'],
                    'tgl_kejadian_laka' => $request->jaminan['penjamin']['tglKejadian'],
                    'keterangan' => $request->jaminan['penjamin']['keterangan'],
                    'suplesi' => $request->jaminan['penjamin']['suplesi']['suplesi'],
                    'nosuplesi' => $request->jaminan['penjamin']['suplesi']['noSepSuplesi'],
                    'kdpropinsi' => $request->jaminan['penjamin']['suplesi']['lokasiLaka']['kdPropinsi'],
                    // 'propinsi',
                    'kdkabupaten' => $request->jaminan['penjamin']['suplesi']['lokasiLaka']['kdKabupaten'],
                    // kabupaten,
                    'kdkecamatan' => $request->jaminan['penjamin']['suplesi']['lokasiLaka']['kdKecamatan'],
                    // kecamatan,
                    // kodedokterdpjp,
		            // dokterdpjp
                ]
            );
        }


       return $createsep;
    }
}
