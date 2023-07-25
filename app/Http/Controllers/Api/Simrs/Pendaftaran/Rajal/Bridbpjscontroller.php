<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal;

use App\Helpers\BridgingbpjsHelper;
use App\Helpers\DateHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use App\Models\Simrs\Pendaftaran\Rajalumum\Seprajal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Bridbpjscontroller extends Controller
{
    public function createsep(Request $request)
    {
        // return new JsonResponse($request->all());
        $tglsep = DateHelper::getDate();
        $assesmentPel = $request->assesmentPel === '' || $request->assesmentPel === null ? '' : $request->assesmentPel;
        $flagprocedure = $request->flagprocedure === '' || $request->flagprocedure === null ? '' : $request->flagprocedure;
        $kdPenunjang = $request->kdPenunjang === '' || $request->kdPenunjang === null ? '' : $request->kdPenunjang;
        $catatan = $request->catatan === null ? '' : $request->catatan;
        $tglKecelakaan = $request->tglKecelakaan === null ? '' : $request->tglKecelakaan;
        $keterangan = $request->keterangan === null ? '' : $request->keterangan;
        $nosepsuplesi = $request->nosepsuplesi === null ? '' : $request->nosepsuplesi;
        $kodepropinsikecelakaan = $request->kodepropinsikecelakaan === null ? '' : $request->kodepropinsikecelakaan;
        $kodekabupatenkecelakaan = $request->kodekabupatenkecelakaan === null ? '' : $request->kodekabupatenkecelakaan;
        $kodekecamatankecelakaan = $request->kodekecamatankecelakaan === null ? '' : $request->kodekecamatankecelakaan;
        $nosuratkontrol = $request->nosuratkontrol === null ? '' : $request->nosuratkontrol;

        $data = [
            "request" => [
                "t_sep" => [
                    "noKartu" => $request->noka,
                    // "tglSep" => $tglsep,
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
                        // "asalRujukan" => '2',
                        "tglRujukan" => $request->tglrujukan,
                        // "tglRujukan" => "2023-05-17",
                        "noRujukan" => $request->norujukan,
                        "ppkRujukan" => $request->ppkRujukan,
                        // "ppkRujukan" => "0213R002",
                    ],
                    "catatan" => $catatan,
                    "diagAwal" => $request->kodediagnosa,
                    "poli" => [
                        "tujuan" => $request->kodepolibpjs,
                        "eksekutif" => '0'
                    ],
                    "cob" => [
                        "cob" => '0'
                    ],
                    "katarak" => [
                        "katarak" => $request->katarak
                    ],
                    "jaminan" => [
                        "lakaLantas" => $request->lakalantas,
                        "noLP" => "",
                        "penjamin" => [
                            "tglKejadian" => $tglKecelakaan,
                            "keterangan" => $keterangan,
                            "suplesi" => [
                                "suplesi" => $request->suplesi,
                                "noSepSuplesi" => $nosepsuplesi,
                                "lokasiLaka" => [
                                    "kdPropinsi" => $kodepropinsikecelakaan,
                                    "kdKabupaten" => $kodekabupatenkecelakaan,
                                    "kdKecamatan" => $kodekecamatankecelakaan
                                ]
                            ]
                        ]
                    ],
                    /* kontrol
                    "tujuanKunj" => '1',
                    "flagProcedure" => '0', default // * harus ada
                    "kdPenunjang" => '10', default // * harus ada
                    "assesmentPel" => '',
                    */

                    "tujuanKunj" => $request->tujuankunjungan,
                    // "tujuanKunj" => '1',
                    "flagProcedure" => $flagprocedure,
                    // "flagProcedure" => '0',
                    "kdPenunjang" => $kdPenunjang,
                    // "kdPenunjang" => '',
                    "assesmentPel" => $assesmentPel,
                    // "assesmentPel" => '',
                    "skdp" => [
                        "noSurat" => $nosuratkontrol,
                        "kodeDPJP" => $request->dpjp
                    ],
                    // "dpjpLayan" => '17432', // dokter dpjp (rencana kontrol kodeDokter)
                    "dpjpLayan" => $request->dpjp, // dokter dpjp (rencana kontrol kodeDokter)
                    "noTelp" => $request->noteleponhp,
                    // "noTelp" => '085219608688',
                    "user" => auth()->user()->pegawai_id
                ]
            ]
        ];

        // return new JsonResponse($data);


        $createsep = BridgingbpjsHelper::post_url(
            'vclaim',
            'SEP/2.0/insert',
            $data
        );

        Bpjs_http_respon::create(
            [
                'method' => 'POST',
                'request' => $data,
                'respon' => $createsep,
                'url' => '/SEP/2.0/insert',
                'tgl' => DateHelper::getDateTime()
            ]
        );

        $xxx = $createsep['metadata']['code'];
        if ($xxx === 200 || $xxx === '200') {
            // $wew = $createsep['response']['sep'];
            $wew = $createsep['response']->sep;
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
                    'lokasilaka' => $request->lakalantas,
                    'penjaminlaka' => '$request->norm',
                    'users' => auth()->user()->pegawai_id,
                    'notelepon' => $request->noteleponhp,
                    'tgl_entery' => DateHelper::getDateTime(),
                    'noDpjp' => $request->noDpjp,
                    'tgl_kejadian_laka' => $request->tglKecelakaan,
                    'keterangan' => $request->keterangan,
                    'suplesi' => $request->suplesi,
                    'nosuplesi' => $request->nosepsuplesi,
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


        return $createsep;
    }

    public function hapussep(Request $request)
    {
        $data = [
            "request" => [
                "t_sep" => [
                    // "noSep" => "1327R0010723V006829",
                    // "noSep" => "1327R0010723V006801",
                    "noSep" => $request->noSep,
                    "user" => '4'
                ]
            ]
        ];
        $hapussep = BridgingbpjsHelper::delete_url(
            'vclaim',
            '/SEP/2.0/delete',
            $data
        );
        return $hapussep;
    }

    public function rencanakontrol()
    {
        $data = [
            "request" => [
                "noSEP" => "1327R0010523V004291",
                "kodeDokter" => "17432",
                "poliKontrol" => "BED",
                "tglRencanaKontrol" => DateHelper::getDate(),
                "user" => "sasa"
            ]
        ];
        $kontrol = BridgingbpjsHelper::post_url('vclaim', '/RencanaKontrol/insert', $data);
        return $kontrol;
    }

    public function createSPRI()
    {
    }

    public function cariseppeserta()
    {
        $sep = '1327R0010523V004291';
        $a = BridgingbpjsHelper::get_url('vclaim', 'SEP/' . $sep);
        return $a;
    }

    public function cari_rujukan()
    {
        $rujukan = '0213R0020523B000114';
        $rujukanPcare = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/' . $rujukan);
        return $rujukanPcare;
    }

    public function cari_rujukan_rs()
    {
        $rujukan = '0123R0020523B000114';
        $rujukanRs = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/RS/0123R0020523B000114');
        return $rujukanRs;
    }
    public function ref_dokter()
    {
        // $rujukan = '0213R0020523B000114';
        $rujukanRs = BridgingbpjsHelper::get_url('antrean', 'ref/dokter');
        return $rujukanRs;
    }
    public function ref_jadwal_dokter_by_politgl()
    {
        $hrIni = DateHelper::getDate();
        $kdPoli = 'BED';

        $param = "$kdPoli/tanggal/$hrIni";
        // return $param;
        $rujukanRs = BridgingbpjsHelper::get_url('antrean', 'jadwaldokter/kodepoli/' . $param);
        return $rujukanRs;
    }
}
