<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Bpjs\BpjsAntrian;
use App\Models\Simrs\Pendaftaran\Rajalumum\Antrianlog;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjsrespontime;
use App\Models\Simrs\Pendaftaran\Rajalumum\Logantrian;
use App\Models\Simrs\Pendaftaran\Rajalumum\Seprajal;
use App\Models\Simrs\Pendaftaran\Rajalumum\Unitantrianbpjs;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BridantrianbpjsController extends Controller
{
    public static function addantriantobpjs($input, $request)
    {
        if ($request->jkn === 'JKN') {
            $jenispasien = "JKN";
        } else {
            $jenispasien = "Non JKN";
        }

        $tgl = Carbon::now()->format('Y-m-d 00:00:00');
        $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        $jmlkunjunganpoli = KunjunganPoli::where('rs1', $input->noreg)->count();
        $unit_antrian = Unitantrianbpjs::select('kuotajkn', 'kuotanonjkn')
            ->where('pelayanan_id', '=', $request->kodepoli)->get();
        $kuotajkn = $unit_antrian[0]->kuotajkn;
        $kuotanonjkn = $unit_antrian[0]->kuotanonjkn;

        $sisakuotajkn = (int)$kuotajkn - $jmlkunjunganpoli;
        $sisakuotanonjkn = (int)$kuotanonjkn - $jmlkunjunganpoli;

        $date = Carbon::parse($request->tanggalperiksa);
        $dt = $date->addMinutes(10);
        $estimasidilayani = $dt->getPreciseTimestamp(3);

        $data =
            [
                "kodebooking" => $input->noreg,
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
            'antrean/add',
            $data
        );

        $simpanbpjshttprespon = Bpjs_http_respon::firstOrCreate(
            [
                'method' => 'POST',
                'request' => $data,
                'url' => '/antrean/add',
                'tgl' => date('Y-m-d H:i:s')
            ]
        );
        //return $ambilantrian;
    }

    public function batalantrian()
    {
        $data = [
            "kodebooking" => "48426/07/2023/J",
            "keterangan" => "testing ws",
        ];
        $batalantrian = BridgingbpjsHelper::post_url(
            'antrean',
            'antrean/batal',
            $data
        );
        return ($batalantrian);
    }

    public static function updateWaktu($input, $x)
    {
        // return $input;
        $kodebooking = $input->noreg;
        $user_id = auth()->user()->pegawai_id;
        $waktu = strtotime(date('Y-m-d H:i:s')) * 1000;
        $bpjsantrian = BpjsAntrian::select('kodebooking')->where('noreg', $kodebooking);
        $wew = $bpjsantrian->count();
        if ($wew > 0) {
            $cari = $bpjsantrian->get();
            $kodebooking = $cari[0]->kodebooking;
        }
        $simpanbpjsrespontime = Bpjsrespontime::create(
            ['kodebooking' => $kodebooking],
            [
                'noreg' => $input->noreg,
                'taskid' => $x,
                'waktu' => $waktu,
                'user_id' => $user_id
            ]
        );
        $data = [
            "kodebooking" => $kodebooking,
            "taskid" => $x,
            'waktu' => $waktu
        ];
        $updatewaktuantrian = BridgingbpjsHelper::post_url(
            'antrean',
            'antrean/updatewaktu',
            $data
        );
    }

    public static function updateMulaiWaktuTungguAdmisi($request, $input)
    {
        $taskid = 1;
        $kodebooking = $input->noreg;
        $user_id = auth()->user()->pegawai_id;
        $anu = BpjsAntrian::query();
        $bpjsantrian = $anu->select('kodebooking')->where('noreg', $kodebooking);
        $wew = $bpjsantrian->count();
        if ($wew > 0) {
            $cari = $bpjsantrian->get();
            $kodebooking = $cari[0]->kodebooking;
        }
        $tgl = date('Y-m-d');
        $antrianlog = Antrianlog::select('booking_type', 'waktu_ambil_tiket')->where('nomor', $request->noantrian)
            ->wheredate('waktu_ambil_tiket', $tgl)->get();
        //return($antrianlog);
        if (count($antrianlog) > 0) {
            $booking_type = $antrianlog[0]->booking_type;
            $waktu_ambil_tiket = $antrianlog[0]->waktu_ambil_tiket;
            if ($booking_type === 'b') {
                $logantrian = Logantrian::select('tgl')->where('noreg', $input->noreg)->wheredate('tgl', $tgl)->get();
                $waktu_ambil_tiket = $logantrian[0]->tgl;
            }
            $waktu_ambil_tiket = $waktu_ambil_tiket;
            $waktu = strtotime($waktu_ambil_tiket) * 1000;

            $simpanbpjsrespontime = Bpjsrespontime::create(
                ['kodebooking' => $kodebooking],
                [
                    'noreg' => $input->noreg,
                    'taskid' => $taskid,
                    'waktu' => $waktu,
                    'created_at' => date('Y-m-d H:i:s'),
                    'user_id' => $user_id
                ]
            );

            $data = [
                "kodebooking" => $kodebooking,
                "taskid" => $taskid,
                'waktu' => $waktu
            ];
            $updatewaktuantrian = BridgingbpjsHelper::post_url(
                'antrean',
                'antrean/updatewaktu',
                $data
            );
        }

        //  return($updatewaktuantrian);
    }

    public static function updateAkhirWaktuTungguAdmisi($input)
    {
        $taskid = '2';
        $kodebooking = $input->noreg;
        $user_id = auth()->user()->pegawai_id;

        $bpjsantrian = BpjsAntrian::select('kodebooking')->where('noreg', $kodebooking);
        $wew = $bpjsantrian->count();
        if ($wew > 0) {
            $cari = $bpjsantrian->get();
            $kodebooking = $cari[0]->kodebooking;
            // return new JsonResponse($kodebooking);
        }
        $tgl = date('Y-m-d');
        $logantrian = Logantrian::select('tgl')->where('noreg', $input->noreg)->wheredate('tgl', $tgl)->get();
        $waktu_ambil_tiket = $logantrian[0]->tgl;
        $waktu = strtotime($waktu_ambil_tiket) * 1000;

        $simpanbpjsrespontime = Bpjsrespontime::create(
            ['kodebooking' => $kodebooking],
            [
                'noreg' => $input->noreg,
                'taskid' => $taskid,
                'waktu' => $waktu,
                'user_id' => $user_id
            ]
        );

        $data = [
            "kodebooking" => $kodebooking,
            "taskid" => $taskid,
            'waktu' => $waktu
        ];
        $updatewaktuantrian = BridgingbpjsHelper::post_url(
            'antrean',
            'antrean/updatewaktu',
            $data
        );
    }
}
