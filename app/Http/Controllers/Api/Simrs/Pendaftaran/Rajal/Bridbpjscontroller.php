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

    public function ambilantrean()
    {
        $data =
        [
            "kodebooking" => "48426/07/2023/J",
            "jenispasien" => "JKN",
            "nomorkartu" => "0001702018012",
            "nik" => "3574054201930001",
            "nohp" => "085204902837",
            "kodepoli" => "ORT",
            "namapoli" => "ORTHOPEDI",
            "pasienbaru" => 1,
            "norm" => "254729",
            "tanggalperiksa" => "2023-07-06",
            "kodedokter" => 17433,
            "namadokter" => "dr. M. Andrie Wibowo, Sp. OT",
            "jampraktek" => "08:00-13:00",
            "jeniskunjungan" => 4,
            "nomorreferensi" => "0213R0020723B000022",
            "nomorantrean" => 'B118',
            "angkaantrean" => 18,
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
}
