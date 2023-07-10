<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BridantrianbpjsController extends Controller
{
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
