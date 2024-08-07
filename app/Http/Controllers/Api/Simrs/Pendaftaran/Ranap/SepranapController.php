<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Ranap;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Pendaftaran\Ranap\Sepranap;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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



    public function create_sep_ranap(Request $request)
    {
        
        $req = [
            "request" => [
                "t_sep" => [
                    "noKartu" => $request->noKartu ?? "",
                    "tglSep" => $request->tglSep ?? Carbon::now()->toDateString(),
                    "ppkPelayanan" => "1327R001",
                    "jnsPelayanan" => $request->jnsPelayanan ?? "1", //1. Rawat Inap, 2. Rawat Jalan
                    "klsRawat" => [
                        "klsRawatHak" => $request->klsRawat->klsRawatHak ?? '',
                        "klsRawatNaik" => $request->klsRawat->klsRawatNaik ?? '',
                        "pembiayaan" => $request->klsRawat->pembiayaan ?? '',
                        "penanggungJawab" => $request->klsRawat->penanggungJawab ?? '',
                    ],
                    "noMR" => $request->noMR ?? "",
                    "rujukan" => [
                        "asalRujukan" => $request->rujukan->asalRujukan ?? '',
                        "tglRujukan" => $request->rujukan->tglRujukan ?? '',
                        "noRujukan" => $request->rujukan->noRujukan ?? '',
                        "ppkRujukan" => $request->rujukan->ppkRujukan ?? ''
                    ],
                    "catatan" => $request->catatan ?? '-',
                    "diagAwal" => "E10",
                    "poli" => ["tujuan" => "", "eksekutif" => "0"],
                    "cob" => ["cob" => "0"],
                    "katarak" => ["katarak" => "0"],
                    "jaminan" => [
                        "lakaLantas" => "0",
                        "noLP" => "12345",
                        "penjamin" => [
                            "tglKejadian" => "",
                            "keterangan" => "",
                            "suplesi" => [
                                "suplesi" => "0",
                                "noSepSuplesi" => "",
                                "lokasiLaka" => [
                                    "kdPropinsi" => "",
                                    "kdKabupaten" => "",
                                    "kdKecamatan" => "",
                                ],
                            ],
                        ],
                    ],
                    "tujuanKunj" => "0",
                    "flagProcedure" => "",
                    "kdPenunjang" => "",
                    "assesmentPel" => "",
                    "skdp" => [
                        "noSurat" => "0301R0110721K000021",
                        "kodeDPJP" => "31574",
                    ],
                    "dpjpLayan" => "",
                    "noTelp" => "081111111101",
                    "user" => $request->user ?? '-',
                ],
            ],
        ];


       return new JsonResponse(['message' => $req], 200);
    }
}
