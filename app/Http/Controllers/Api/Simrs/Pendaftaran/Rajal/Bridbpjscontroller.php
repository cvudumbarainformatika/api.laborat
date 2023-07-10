<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Bridbpjscontroller extends Controller
{
    public function createsep(Request $request)
    {

        $data =[
            "request"=>[
                "t_sep"=>[
                    "noKartu" => $request->noka,
                    "tglSep" => $request->tglsep,
                    "ppkPelayanan" => '1327R001',
                    "jnsPelayanan" => '2',
                    "klsRawat"=>[
                        "klsRawatHak" => '1',
                        "klsRawatNaik" => '',
                        "pembiayaan" => '',
                        "penanggungJawab" => '',
                    ],
                    "noMR" => $request->norm,
                    "rujukan"=>[
                        "asalRujukan"=>"1",
                        "tglRujukan"=>"2023-06-21",
                        "noRujukan"=>"132701010623Y001229",
                        "ppkRujukan"=>"13270101"
                    ],
                    "catatan"=>"testinsert RJ",
                    "diagAwal"=>"M51",
                    "poli"=>[
                        "tujuan"=>"ORT",
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
                        "noLP"=>"",
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
                     "tujuanKunj"=>"2",
                     "flagProcedure"=>"",
                     "kdPenunjang"=>"",
                     "assesmentPel"=>"5",
                     "skdp"=>[
                        "noSurat"=>"1327R0010723K000230",
                        "kodeDPJP"=>"17433"
                     ],
                     "dpjpLayan"=>"17433",
                     "noTelp"=>"081232687158",
                     "user"=>"Coba Ws"
                ]
            ]
        ];
        $createsep = BridgingbpjsHelper::post_url(
            'vclaim',
            '/SEP/2.0/insert', $data);
        return($createsep);
    }

    public function hapussep(Request $request)
    {
        $data = [
            "request" => [
                "t_sep" => [
                    "noSep" => "0301R0011017V000007",
                    "user" => "Coba Ws"
                ]
            ]
        ];
        $hapussep = BridgingbpjsHelper::post_url(
            'vclaim',
            '/SEP/2.0/delete', $data
        );
        return($hapussep);
    }

    public static function addantriantobpjs($request,$input)
    {
        if($request->jkn === 'JKN')
        {
            $jenispasien = "JKN";
        }else{
            $jenispasien = "Non JKN";
        }
        $data =
        [
            "kodebooking" => $input,
            "jenispasien" => $jenispasien,
            "nomorkartu" => $request->noka,
            "nik" => $request->nik,
            "nohp" => $request->nohp,
            "kodepoli" => $request->kodepoli,
            "namapoli" => $request->namapoli,
            "pasienbaru" => $request->jenispasien,
            "norm" => $request->norm,
            "tanggalperiksa" => $request->tglsep,
            "kodedokter" => $request->dpjp,
            "namadokter" => $request->namadokter,
            "jampraktek" => $request->jamperkatek,
            "jeniskunjungan" => $request->id_kunjungan,
            "nomorreferensi" => $request->norujukan,
            "nomorantrean" => $request->noantrian,
            "angkaantrean" => 6,
            "estimasidilayani" => 1688613900000,
            "sisakuotajkn" => 330,
            "kuotajkn" => 24,
            "sisakuotanonjkn" => -32,
            "kuotanonjkn" => 6,
            "keterangan" => "Peserta harap 30 menit lebih awal guna pencatatan administrasi."
        ];
        $ambilantrian = BridgingbpjsHelper::post_url(
            'antrean',
            'antrean/add', $data
        );
        return($ambilantrian);
    }

    public function batalantrian()
    {
        $data = [
            "kodebooking" => "48426/07/2023/J",
            "keterangan" => "testing ws",
        ];
        $batalantrian = BridgingbpjsHelper::post_url(
            'antrean',
            'antrean/batal', $data
        );
        return($batalantrian);
    }
}
