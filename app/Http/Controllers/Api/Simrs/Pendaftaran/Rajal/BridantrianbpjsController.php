<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use App\Models\Simrs\Pendaftaran\Rajalumum\Unitantrianbpjs;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BridantrianbpjsController extends Controller
{
    // public function wewxx(Request $request)
    // {
    //     $wew = strtotime(Carbon::now()->addMonths(2));
    //     return($wew);
    // }

    public static function addantriantobpjs($request,$input)
    {
        if($request->jkn === 'JKN')
        {
            $jenispasien = "JKN";
        }else{
            $jenispasien = "Non JKN";
        }

        $tgl = Carbon::now()->format('Y-m-d 00:00:00');
        $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        $jmlkunjunganpoli= KunjunganPoli::whereBetween('rs17.rs3', [$tgl, $tglx])->count();

        $unit_antrian = Unitantrianbpjs::select('kuotajkn','kuotanonjkn')
        ->where('pelayanan_id', '=', $request->kodepoli)->get();
        $kuotajkn = $unit_antrian[0]->kuotajkn;
        $kuotanonjkn = $unit_antrian[0]->kuotanonjkn;

        $sisakuotajkn = $kuotajkn-$jmlkunjunganpoli;
        $sisakuotanonjkn = $kuotanonjkn-$jmlkunjunganpoli;

        $date = Carbon::parse($request->tanggalperiksa);
        $dt = $date->addMinutes(10);
        $estimasidilayani = $dt->getPreciseTimestamp(3);

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
            "jampraktek" => $request->jampraktek,
            "jeniskunjungan" => $request->id_kunjungan,
            "nomorreferensi" => $request->norujukan,
            "nomorantrean" => $request->noantrian,
            "angkaantrean" => $request->angkaantrean,
            "estimasidilayani" => $estimasidilayani,
            "sisakuotajkn" => $sisakuotajkn,
            "kuotajkn" => $kuotajkn,
            "sisakuotanonjkn" => $sisakuotanonjkn,
            "kuotanonjkn" => $kuotanonjkn,
            "keterangan" => "Peserta harap 30 menit lebih awal guna pencatatan administrasi."
        ];
        $ambilantrian = BridgingbpjsHelper::post_url(
            'antrean',
            'antrean/add', $data
        );

        $simpanbpjshttprespon = Bpjs_http_respon::firstOrCreate(
        [
            'method' => 'POST',
            'request' => $data,
            'url' => '/antrean/add',
            'tgl' => date('Y-m-d H:i:s')
        ]);
        // $hasil['request']=$data;
        // $hasil['ambilantrian']=$ambilantrian;
        // return($hasil);
        return ($ambilantrian);
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
