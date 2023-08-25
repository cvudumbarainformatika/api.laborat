<?php

namespace App\Http\Controllers\Api\Simrs\Bridgingeklaim;

use App\Helpers\BridgingeklaimHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EwseklaimController extends Controller
{
    public function ewseklaimrajal_newclaim(Request $request)
    {
        $noreg = $request->noreg;
        $norm = $request->norm;
        $namapasien = $request->namapasien;
        $tgl_lahir = $request->tgl_lahir;
        $gender = $request->gender;
        $tgl_masuk = $request->tgl_masuk;
        $berat_lahir = $request->berat_lahir;

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

        if ($response_new_klaim_code == '200') {
            $setclaimdata = self::ews_set_claim_data($noreg, $norm, $tgl_masuk, $berat_lahir);
            if ($setclaimdata["metadata"]["code"] == "200") {
                $grouper = self::ews_grouper($noreg);
                return ($grouper);
            }
        }

        return ($response_new_klaim_message);
    }

    public static function ews_set_claim_data($noreg, $norm, $tgl_masuk, $berat_lahir)
    {
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
                "diagnosa" => "S71.0#A00.1",
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
        return ($response_set_claim_data);
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
        $cbg_code = $responsesx["response"]["cbg"]["code"];
        $cbg_desc = $responsesx["response"]["cbg"]["description"];
        $cbg_tarif = $responsesx["response"]["cbg"]["tariff"];
        $special_cmg_option = $responsesx["special_cmg_option"];
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
        return new JsonResponse([
            'cbg_code' => $cbg_code,
            'cbg_desc' => $cbg_desc,
            'cbg_tarif' => $cbg_tarif,
            'special_cmg_option' => $special_cmg_option,
        ]);
    }
}
