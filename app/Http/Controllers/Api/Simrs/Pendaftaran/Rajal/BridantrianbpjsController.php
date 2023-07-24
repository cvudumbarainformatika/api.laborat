<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal;

use App\Helpers\BridgingbpjsHelper;
use App\Helpers\DateHelper;
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
    public static function addantriantobpjs($noreg, $request)
    {
        if ($request->jkn === 'JKN') {
            $jenispasien = "JKN";
        } else {
            $jenispasien = "Non JKN";
        }

        $tgl = Carbon::now()->format('Y-m-d 00:00:00');
        $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        $jmlkunjunganpoli = KunjunganPoli::where('rs1', $noreg)->count();
        $unit_antrian = Unitantrianbpjs::select('kuotajkn', 'kuotanonjkn')
            ->where('pelayanan_id', '=', $request->kodepoli)->get();
        $kuotajkn = $unit_antrian[0]->kuotajkn;
        $kuotanonjkn = $unit_antrian[0]->kuotanonjkn;

        $sisakuotajkn = (int)$kuotajkn - $jmlkunjunganpoli;
        $sisakuotanonjkn = (int)$kuotanonjkn - $jmlkunjunganpoli;

        $date = Carbon::parse($request->tglsep);
        $dt = $date->addMinutes(10);
        $estimasidilayani = $dt->getPreciseTimestamp(3);

        $pasienbaru = $request->barulama == 'lama' ? 1 : 0;

        $data =
            [
                "kodebooking" => $noreg,
                "jenispasien" => $jenispasien,
                "nomorkartu" => $request->noka,
                "nik" => $request->nik,
                "nohp" => $request->noteleponhp,
                "kodepoli" => $request->kodepolibpjs,
                "namapoli" => $request->namapolibpjs,
                "pasienbaru" => $pasienbaru,
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
                'respon' => $ambilantrian,
                'url' => '/antrean/add',
                'tgl' => DateHelper::getDateTime()
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
        Bpjs_http_respon::firstOrCreate(
            [
                'method' => 'POST',
                'request' => $data,
                'respon' => $batalantrian,
                'url' => 'antrean/batal',
                'tgl' => DateHelper::getDateTime()
            ]
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
            [
                'kodebooking' => $kodebooking,
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
        Bpjs_http_respon::firstOrCreate(
            [
                'method' => 'POST',
                'request' => $data,
                'respon' => $updatewaktuantrian,
                'url' => 'antrean/updatewaktu',
                'tgl' => DateHelper::getDateTime()
            ]
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
            ->whereDate('waktu_ambil_tiket', $tgl)->get();
        //return($antrianlog);
        if (count($antrianlog) > 0) {
            $booking_type = $antrianlog[0]->booking_type;
            $waktu_ambil_tiket = $antrianlog[0]->waktu_ambil_tiket;
            if ($booking_type === 'b') {
                $logantrian = Logantrian::select('tgl')->where('noreg', $input->noreg)->whereDate('tgl', $tgl)->get();
                $waktu_ambil_tiket = $logantrian[0]->tgl;
            }
            $waktu = strtotime($waktu_ambil_tiket) * 1000;

            $simpanbpjsrespontime = Bpjsrespontime::create(
                [
                    'kodebooking' => $kodebooking,
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
            Bpjs_http_respon::firstOrCreate(
                [
                    'method' => 'POST',
                    'request' => $data,
                    'respon' => $updatewaktuantrian,
                    'url' => 'antrean/updatewaktu',
                    'tgl' => DateHelper::getDateTime()
                ]
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
            [
                'kodebooking' => $kodebooking,
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
        Bpjs_http_respon::firstOrCreate(
            [
                'method' => 'POST',
                'request' => $data,
                'respon' => $updatewaktuantrian,
                'url' => 'antrean/updatewaktu',
                'tgl' => DateHelper::getDateTime()
            ]
        );
    }
}
