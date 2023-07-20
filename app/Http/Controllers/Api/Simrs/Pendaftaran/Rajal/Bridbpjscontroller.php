<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Pendaftaran\Rajalumum\Seprajal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Bridbpjscontroller extends Controller
{
    public function createsep(Request $request)
    {
        return new JsonResponse($request->all());
        $catatan = $request->catatan ? $request->catatan : '';
        $kodepoli = $request->kodepolibpjs ? $request->kodepolibpjs : '';
        $kecelakaan = $request->lakalantas ? $request->lakalantas : '';
        $tglkecelakaan = $request->tglKecelakaan ? $request->tglKecelakaan : '';
        $keterangan = $request->keterangan ? $request->keterangan : '';
        $suplesi = $request->suplesi ? $request->suplesi : '';
        $nosepsuplesi = $request->nosepsuplesi ? $request->nosepsuplesi : '';
        $$kdpropinsi = $request->kodepropinsikecelakaan ? $request->kodepropinsikecelakaan : '';
        $$kdkabupaten = $request->kodekabupatenkecelakaan ? $request->kodekabupatenkecelakaan : '';
        $$kdkecamatan = $request->kodekecamatankecelakaan ? $request->kodekecamatankecelakaan : '';
        $namadokterdpjp = $request->namadokter ? $request->namadokter : '';


        $flagprocedure = $request->flagprocedure ? $request->flagprocedure : '';
        $kdPenunjang = $request->kdPenunjang ? $request->kdPenunjang : '';
        $assesmenPel = $request->assesmenPel ? $request->assesmenPel : '';
        $nosurat = $request->nosuratkontrol ? $request->nosuratkontrol : '';
        $kddpjp = $request->dpjp ? $request->dpjp : '';
        $notelepon = $request->noteleponhp ? $request->noteleponhp : '';
        $data = [
            "request" => [
                "t_sep" => [
                    "noKartu" => $request->noka,
                    "tglSep" => $request->tglsep,
                    // "ppkPelayanan" => $request->ppkpelayanan, //'1327R001'
                    "ppkPelayanan" => '1327R001',
                    "jnsPelayanan" => $request->jnspelayanan,
                    "klsRawat" => [
                        "klsRawatHak" => $request->hakkelas,
                        "klsRawatNaik" => '',
                        "pembiayaan" => '',
                        "penanggungJawab" => '',
                    ],
                    "noMR" => $request->norm,
                    "rujukan" => [
                        "asalRujukan" => $request->asalRujukan,
                        "tglRujukan" => $request->tglrujukan,
                        "noRujukan" => $request->norujukan,
                        "ppkRujukan" => $request->ppkRujukan,
                    ],
                    "catatan" => $catatan,
                    "diagAwal" => $request->kodediagnosa,
                    "poli" => [
                        "tujuan" => $kodepoli,
                        "eksekutif" => '0'
                    ],
                    "cob" => [
                        "cob" => '0'
                    ],
                    "katarak" => [
                        "katarak" => $request->katarak
                    ],
                    "jaminan" => [
                        "lakaLantas" => $kecelakaan,
                        "noLP" => "",
                        "penjamin" => [
                            "tglKejadian" => $tglkecelakaan,
                            "keterangan" => $keterangan,
                            "suplesi" => [
                                "suplesi" => $suplesi,
                                "noSepSuplesi" => $nosepsuplesi,
                                "lokasiLaka" => [
                                    "kdPropinsi" => $kdpropinsi,
                                    "kdKabupaten" => $kdkabupaten,
                                    "kdKecamatan" => $kdkecamatan
                                ]
                            ]
                        ]
                    ],
                    "tujuanKunj" => $request->tujuankunjungan,
                    "flagProcedure" => $flagprocedure,
                    "kdPenunjang" => $kdPenunjang,
                    "assesmentPel" => $assesmenPel,
                    "skdp" => [
                        "noSurat" => $nosurat,
                        "kodeDPJP" => $kddpjp
                    ],
                    "dpjpLayan" => '000002',
                    "noTelp" => $notelepon,
                    "user" => auth()->user()->pegawai_id
                ]
            ]
        ];

        // $data =[
        //     "request"=>[
        //         "t_sep"=>[
        //             "noKartu" => '',
        //             "tglSep" => '2023-07-11',
        //             "ppkPelayanan" => '1327R001', //'1327R001'
        //             "jnsPelayanan" => '2',
        //             "klsRawat"=>[
        //                 "klsRawatHak" => '3',
        //                 "klsRawatNaik" => '',
        //                 "pembiayaan" => '',
        //                 "penanggungJawab" => '',
        //             ],
        //             "noMR" => '215501',
        //             "rujukan"=>[
        //                 "asalRujukan"=> '2',
        //                 "tglRujukan"=> '2023-05-06',
        //                 "noRujukan"=> '0213B0080623P000076',
        //                 "ppkRujukan"=> '0213B008',
        //             ],
        //             "catatan"=> '',
        //             "diagAwal"=> 'S62.8',
        //             "poli"=>[
        //                 "tujuan"=> 'ORT',
        //                 "eksekutif"=> '0'
        //             ],
        //             "cob"=>[
        //                 "cob"=> '0'
        //              ],
        //              "katarak"=>[
        //                 "katarak"=> '0'
        //              ],
        //              "jaminan"=>[
        //                 "lakaLantas"=> '0',
        //                 "noLP"=> "",
        //                 "penjamin"=>[
        //                    "tglKejadian"=> '',
        //                    "keterangan"=> '',
        //                    "suplesi"=>[
        //                       "suplesi"=> '0',
        //                       "noSepSuplesi"=> '',
        //                       "lokasiLaka"=>[
        //                          "kdPropinsi"=> '',
        //                          "kdKabupaten"=> '',
        //                          "kdKecamatan"=> ''
        //                       ]
        //                    ]
        //                 ]
        //              ],
        //              "tujuanKunj"=> '0',
        //              "flagProcedure"=> '',
        //              "kdPenunjang"=> '',
        //              "assesmentPel"=> '',
        //              "skdp"=>[
        //                 "noSurat"=> '1327R0010623K004588',
        //                 "kodeDPJP"=> '17433'
        //              ],
        //              "dpjpLayan"=> '',
        //              "noTelp"=> '081336604505',
        //              "user"=> auth()->user()->pegawai_id
        //         ]
        //     ]
        // ];
        $createsep = BridgingbpjsHelper::post_url(
            'vclaim',
            '/SEP/2.0/insert',
            $data
        );
        $xxx = $createsep['metaData']['code'];
        if ($xxx === 200 || $xxx === '200') {
            $wew = $createsep['response']['sep'];
            $poliBpjs = $wew['poli'];
            $nosep = $wew['noSep'];
            $dinsos = $wew['informasi'];
            $prolanisPRB = $wew['informasi']['prolanisPRB'];
            $noSKTM = $wew['informasi']['noSKTM'];
            $nosep = $wew['noSep'];
            $insertsep = Seprajal::firsOrCreate(
                ['rs1' => $request->noreg],
                [
                    'rs2' => $request->norm,
                    'rs3' => $poliBpjs,
                    'rs4' => $request->sistembayar,
                    'rs5' => $request->norujukan,
                    'rs6' => $request->tglrujukan,
                    'rs7' => $request->namadiagnosa,
                    'rs8' => $nosep,
                    'rs9' => $request->catatan,
                    'rs10' => $request->ppkrujukan,
                    'rs11' => $request->jenispeserta,
                    'rs12' => $request->tglkunjungan,
                    'rs13' => $request->noka,
                    'rs14' => $request->nama,
                    'rs15' => $request->tgllahir,
                    'rs16' => $request->jeniskelamin,
                    'rs17' => $request->jenisrawat,
                    'rs18' => $request->kelas,
                    'laka' => $request->kecelakaan,
                    'lokasilaka' => $kecelakaan,
                    'penjaminlaka' => $request->norm,
                    'users' => auth()->user()->pegawai_id,
                    'notelepon' => $notelepon,
                    'tgl_entery' => date('Y-m-d H:i:s'),
                    'noDpjp' => $request->noDpjp,
                    'tgl_kejadian_laka' => $tglkecelakaan,
                    'keterangan' => $keterangan,
                    'suplesi' => $suplesi,
                    'nosuplesi' => $nosepsuplesi,
                    'kdpropinsi' => $request->kodepropinsikecelakaan,
                    'propinsi' => $request->propinsikecelakaan,
                    'kdkabupaten' => $request->kodekabupatenkecelakaan,
                    'kabupaten' => $request->kabupatenkecelakaan,
                    'kdkecamatan' => $request->kodekecamatankecelakaan,
                    'kecamatan' => $request->kecamatankecelakaan,
                    'kodedokterdpjp' => $request->dpjp,
                    'dokterdpjp' => $request->namadokter,
                    'kodeasalrujuk' => $request->ppkRujukan,
                    'namaasalperujuk' => $request->namappkRujukan,
                    'Dinsos' => $request->dinsos,
                    'prolanisPRB' => $request->prolanisPRB,
                    'noSKTM' => $request->noSKTM,
                    'jeniskunjungan' => $request->jenis_kunjungan,
                    'tujuanKunj' => $request->tujuankunjungan,
                    'flagProcedure' => $request->flagprocedure,
                    'kdPenunjang' => $request->kdPenunjang,
                    'assesmentPel' => $request->assesmentPel,
                    'kdUnit' => $request->kdUnit
                ]
            );
        }
        return ($createsep);
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
            '/SEP/2.0/delete',
            $data
        );
        return ($hapussep);
    }
}
