<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Rajalumum;

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
                    "noKartu" => '0001449177478',
                    "tglSep" => '2023-06-28',
                    "ppkPelayanan" => '1327R001',
                    "jnsPelayanan" => '2',
                    "klsRawat"=>[
                        "klsRawatHak" => '1',
                        "klsRawatNaik" => '',
                        "pembiayaan" => '',
                        "penanggungJawab" => '',
                    ],
                    "noMR" => '078108',
                    "rujukan"=>[
                        "asalRujukan"=>"1",
                        "tglRujukan"=>"2023-05-29",
                        "noRujukan"=>"132703010523P001443",
                        "ppkRujukan"=>"13270301"
                    ],
                    "catatan"=>"testinsert RJ",
                    "diagAwal"=>"J44",
                    "poli"=>[
                        "tujuan"=>"PAR",
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
                        "noSurat"=>"1327R0010623K001958",
                        "kodeDPJP"=>"14653"
                     ],
                     "dpjpLayan"=>"270109",
                     "noTelp"=>"081233270700",
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
}
