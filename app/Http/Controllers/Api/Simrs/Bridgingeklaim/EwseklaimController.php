<?php

namespace App\Http\Controllers\Api\Simrs\Bridgingeklaim;

use App\Helpers\BridgingeklaimHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Ews\GroupingRajalEws;
use App\Models\Simrs\Ews\KlaimrajalEws;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosa;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EwseklaimController extends Controller
{
    public static function ewseklaimrajal_newclaim(Request $request)
    {
        $noreg = $request->noreg;
        $carirajal = KunjunganPoli::select('rs2', 'rs3', 'rs25')->with('masterpasien:rs1,rs2,rs16,rs17,berat_lahir')
            ->where('rs1', $noreg)
            ->where('rs14', 'Like', '%BPJS%')->get();
        $norm = $carirajal[0]['rs2'];
        $hakkelas = $carirajal[0]['rs25'];
        $namapasien = $carirajal[0]['masterpasien']['rs2'];
        $tgl_lahir = $carirajal[0]['masterpasien']['rs16'];
        $kelamin = $carirajal[0]['masterpasien']['rs17'];
        $tgl_masuk = $carirajal[0]['rs3'];
        $berat_lahirs = $carirajal[0]['masterpasien']['berat_lahir'];
        $berat_lahir = str_replace('.', '', $berat_lahirs);

        if ($kelamin == 'Perempuan') {
            $gender = '2';
        } elseif ($kelamin == 'Laki-laki') {
            $gender = '1';
        } else {
            $gender = '';
        }

        $klaimrajal = KlaimrajalEws::where('noreg', $noreg)->count();

        if ($klaimrajal === 0) {
            $querys_new_klaim = array(
                "metadata" => array(
                    "method" => "new_claim"
                ),
                "data" => array(
                    "nomor_kartu" => $norm,
                    "nomor_sep" => $noreg,
                    "nomor_rm" => $norm,
                    "nama_pasien" => $namapasien,
                    "tgl_lahir" =>  $tgl_lahir . ' 02:00:00',
                    "gender" => $gender,
                )
            );
            $response_new_klaim = BridgingeklaimHelper::curl_func($querys_new_klaim);
            $response_new_klaim_code = $response_new_klaim["metadata"]["code"];
            $response_new_klaim_message = $response_new_klaim["metadata"]["message"];

            if ($response_new_klaim_code === '200') {
                KlaimrajalEws::create(['noreg' => $noreg]);

                $setclaimdata = self::ews_set_claim_data($noreg, $norm, $tgl_masuk, $berat_lahir, $hakkelas);
                if ($setclaimdata["metadata"]["code"] == "200") {
                    $grouper = self::ews_grouper($noreg);
                    return ($grouper);
                }
            }

            return ($response_new_klaim_message);
        }
        $setclaimdata = self::ews_set_claim_data($noreg, $norm, $tgl_masuk, $berat_lahir, $hakkelas);
        if ($setclaimdata["metadata"]["code"] == "200") {
            $grouper = self::ews_grouper($noreg);
            return ($grouper);
        }
    }

    public static function ews_set_claim_data($noreg, $norm, $tgl_masuk, $berat_lahir, $hakkelas)
    {

        $diagnosa = self::caridiagnosa($noreg);
        $querys_set_claim_data = array(
            "metadata" => array(
                "method" => "set_claim_data",
                "nomor_sep" => $noreg
            ),
            "data" => array(
                "nomor_sep" => $noreg,
                "nomor_kartu" => $norm,
                "tgl_masuk" => $tgl_masuk,
                "tgl_pulang" => date("Y-m-d H:i:s"),
                "jenis_rawat" => 2,
                "kelas_rawat" => 3,
                "adl_sub_acute" => '',
                "adl_chronic" => '',
                "icu_indikator" => '',
                "icu_los" => '',
                "ventilator_hour" => '',
                "upgrade_class_ind" => '',
                "upgrade_class_class" => '',
                "upgrade_class_los" => '',
                "add_payment_pct" => '',
                "birth_weight" => $berat_lahir,
                "discharge_status" => 1,
                "diagnosa" => $diagnosa,
                // "diagnosa" => "S71.0#A00.1",
                "procedure" => "81.52#88.38",
                "tarif_rs" => array(
                    "prosedur_non_bedah" => 0,
                    "prosedur_bedah" => 0,
                    "konsultasi" => 0,
                    "tenaga_ahli" => 0,
                    "keperawatan" => 0,
                    "penunjang" => 0,
                    "radiologi" => 0,
                    "laboratorium" => 0,
                    "pelayanan_darah" => 0,
                    "rehabilitasi" => 0,
                    "kamar" => 0,
                    "rawat_intensif" => 0,
                    "obat" => 0,
                    "obat_kronis" => 0,
                    "obat_kemoterapi" => 0,
                    "alkes" => 0,
                    "bmhp" => 0,
                    "sewa_alat" => 0
                ),
                "tarif_poli_eks" => 0,
                "nama_dokter" => 'dokter',
                "kode_tarif" => 'BP',
                "payor_id" => 3,
                "payor_cd" => 'JKN',
                "cob_cd" => '',
                "coder_nik" => '123123123123'
            )
        );

        $response_set_claim_data = BridgingeklaimHelper::curl_func($querys_set_claim_data);
        if ($response_set_claim_data["metadata"]["code"] == "200") {
            KlaimrajalEws::where(['noreg' => $noreg, 'delete_status' => ''])
                ->update(
                    [
                        'kelas_rawat' => $hakkelas,
                        'adl_sub_acute' => '',
                        'adl_chronic' => '',
                        'icu_indikator' => '',
                        'icu_los' => '',
                        'ventilator_hour' => '',
                        'upgrade_class_ind' => '',
                        'upgrade_class_class' => '',
                        'upgrade_class_los' => '',
                        'add_payment_pct' => '',
                        'birth_weight' => '',
                        'discharge_status' => '1',
                        'diagnosas' => $diagnosa,
                        'procedures' => '".$prosedur."',
                        'prosedur_non_bedah' => '0',
                        'prosedur_bedah' => '0',
                        'konsultasi' => '0',
                        'tenaga_ahli' => '0',
                        'keperawatan' => '0',
                        'penunjang' => '0',
                        'radiologi' => '0',
                        'pelayanan_darah' => '0',
                        'rehabilitasi' => '0',
                        'kamar' => '0',
                        'rawat_intensif' => '0',
                        'obat' => '0',
                        'alkes' => '0',
                        'laboratorium' => '0',
                        'kd_dokter' => '',
                        'bmhp' => '0',
                        'sewa_alat' => '0',
                        'tarif_poli_eks' => '0',
                        'nama_dokter' => '',
                        'kode_tarif' => 'CP',
                        'payor_id' => '3',
                        'payor_cd' => 'JKN',
                        'cob_cd' => '',
                        'coder_nik' => '123123123123',
                        'users_update' => auth()->user()->pegawai_id,
                        'tgl_update' => date("Y-m-d H:i:s"),
                        'konsulke' => '',
                        'status_klaim' => 'Tersimpan'
                    ]
                );
        }
        $groupingrajal = GroupingRajalEws::updateOrCreate(
            ['noreg' => $noreg],
            [
                'nosep' => $noreg,
                'users_grouping' => auth()->user()->pegawai_id,
                'tgl_grouping' => date("Y-m-d H:i:s")
            ]
        );
        $grouper = self::ews_grouper($noreg);
        return ($grouper);
        //return ($response_set_claim_data);
    }

    public static function ews_grouper($noreg)
    {
        $querysx = array(
            "metadata" => array(
                "method" => "grouper",
                "stage" => "1"
            ),
            "data" => array(
                "nomor_sep" => $noreg
            )
        );
        $responsesx = BridgingeklaimHelper::curl_func($querysx);
        //return $responsesx["response"];
        $cbg_code = $responsesx["response"]["cbg"]["code"];
        $cbg_desc = $responsesx["response"]["cbg"]["description"];
        $cbg_tarif = $responsesx["response"]["cbg"]["tariff"];
        //$special_cmg_option = $responsesx["special_cmg_option"];
        //return $cbg_code;
        $procedure_code = "";
        $procedure_desc = "";
        $procedure_tarif = "";
        $prosthesis_code = "";
        $prosthesis_desc = "";
        $prosthesis_tarif = "";
        $investigation_code = "";
        $investigation_desc = "";
        $investigation_tarif = "";
        $drug_code = "";
        $drug_desc = "";
        $drug_tarif = "";
        $opt_cmg = "";
        // return ($responsesx);
        if (isset($responsesx["special_cmg_option"]) ?? null) {
            $opt_cmg = json_encode($responsesx["special_cmg_option"]);
            $special_cmg = "";
            foreach ($responsesx["special_cmg_option"] as $special_cmg_arr) {
                $special_cmg .= $special_cmg_arr["code"] . "#";
            }
            $special_cmg = substr($special_cmg, 0, -1);
            $querysxx = array(
                "metadata" => array(
                    "method" => "grouper",
                    "stage" => "2"
                ),
                "data" => array(
                    "nomor_sep" => $noreg,
                    "special_cmg" => $special_cmg
                )
            );
            $responsesxx = BridgingeklaimHelper::curl_func($querysxx);
            $cbg_code = $responsesxx["response"]["cbg"]["code"];
            $cbg_desc = $responsesxx["response"]["cbg"]["description"];
            $cbg_tarif = $responsesxx["response"]["cbg"]["tariff"];
            if (isset($responsesxx["response"]["special_cmg"])) {
                foreach ($responsesxx["response"]["special_cmg"] as $special_cmg_arrx) {
                    if ($special_cmg_arrx["type"] == "Special Procedure") {
                        $procedure_code = $special_cmg_arrx["code"];
                        $procedure_desc = $special_cmg_arrx["description"];
                        $procedure_tarif = $special_cmg_arrx["tariff"];
                    } elseif ($special_cmg_arrx["type"] == "Special Prosthesis") {
                        $prosthesis_code = $special_cmg_arrx["code"];
                        $prosthesis_desc = $special_cmg_arrx["description"];
                        $prosthesis_tarif = $special_cmg_arrx["tariff"];
                    } elseif ($special_cmg_arrx["type"] == "Special Investigation") {
                        $investigation_code = $special_cmg_arrx["code"];
                        $investigation_desc = $special_cmg_arrx["description"];
                        $investigation_tarif = $special_cmg_arrx["tariff"];
                    } elseif ($special_cmg_arrx["type"] == "Special Drug") {
                        $drug_code = $special_cmg_arrx["code"];
                        $drug_desc = $special_cmg_arrx["description"];
                        $drug_tarif = $special_cmg_arrx["tariff"];
                    }
                }
            }
            KlaimrajalEws::where(['noreg' => $noreg])
                ->update(
                    [
                        'cbg_code' => $cbg_code,
                        'cbg_desc' => $cbg_desc,
                        'cbg_tarif' => $cbg_tarif,
                        'opt_cmg' => $opt_cmg,
                        'tgl_update' => date("Y-m-d H:i:s"),
                        'users_update' => auth()->user()->pegawai_id,
                        'procedure_code' => $procedure_code,
                        'procedure_desc' => $procedure_desc,
                        'procedure_tarif' => $procedure_tarif,
                        'prosthesis_code' => $prosthesis_code,
                        'prosthesis_desc' => $prosthesis_desc,
                        'prosthesis_tarif' => $prosthesis_tarif,
                        'investigation_code' => $investigation_code,
                        'investigation_desc' => $investigation_desc,
                        'investigation_tarif' => $investigation_tarif,
                        'drug_code' => $drug_code,
                        'drug_desc' => $drug_desc,
                        'drug_tarif' => $drug_tarif
                    ]
                );
            return $responsesxx;
        } else {
            return $responsesx;
        }
    }

    public static function caridiagnosa($noreg)
    {
        $cari = Diagnosa::select('rs3')->where('rs1', $noreg)->get();
        foreach ($cari as $val) {
            $wew[] = $val['rs3'] . '#';
            $xxx = implode(',', $wew);
            $diagnosa = str_replace(',', '', $xxx);
        }
        return $diagnosa;
    }
}
