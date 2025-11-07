<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\IT;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Bpjs\BpjsHttpRespon;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjsrespontime;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LaporanAntianRsDanBpjsController extends Controller
{
    //
    public function getListBpjsPost(Request $request)
    {
        // $data = $request->all();
        $data = self::getListFromBpjs(request('tgl'));
        return new JsonResponse(['data' => $data]);
    }
    public function getListBpjs()
    {
        // $data = request()->all();
        $data = self::getListFromBpjs(request('tgl'));
        return new JsonResponse(['data' => $data]);
    }
    public function getOneBpjs()
    {

        // $data = request()->all();
        // $data = self::getOneFromBpjs("");
        $data = self::getOneFromBpjs(request('kode'));
        return new JsonResponse(['data' => $data]);
    }
    public static function getListFromBpjs($request)
    {
        $data = BridgingbpjsHelper::get_url(
            'antrean',
            'antrean/pendaftaran/tanggal/' . $request
        );
        return $data;
    }
    public static function getOneFromBpjs($request)
    {
        // return $request;
        $encoded = urlencode($request);
        $data = BridgingbpjsHelper::get_url(
            'antrean',
            'antrean/pendaftaran/kodebooking/' . $encoded
        );
        return $data;
    }
    public function kirimUlangTaskId(Request $request)
    {

        foreach ($request->task as $task) {
            self::reUpdateWaktu($task);
        }
        $data['req'] = $request->all();
        $data['kunjungan'] = KunjunganPoli::select('rs1', 'rs1 as noreg')->where('rs1', $request->noreg)->with([
            'taskid' => function ($q) {
                $q->orderBy('taskid', 'DESC');
            },
            'bpjshttprespon'
        ])->first();
        $data['antrian'] = self::getOneFromBpjs($request->kodebooking);
        return new JsonResponse([
            'message' => 'Task Id sudah dikirim ulang',
            'data' => $data
        ]);
    }
    public static function reUpdateWaktu($head)
    {

        $kodebooking = $head['kodebooking'];
        $noreg = $head['noreg'];
        $taskid = $head['taskid'];
        $waktu = $head['waktu'];
        $user_id = auth()->user()->pegawai_id;

        $tgltobpjshttpres = date('Y-m-d H:i:s');

        Bpjsrespontime::create(
            [
                'kodebooking' => $kodebooking,
                'noreg' => $noreg,
                'taskid' => $taskid,
                'waktu' => $waktu,
                'created_at' =>  date('Y-m-d H:i:s'),
                'user_id' => $user_id
            ]
        );
        $data = [
            "kodebooking" => $kodebooking,
            "taskid" => $taskid,
            'waktu' => $waktu
        ];
        try {
            $updatewaktuantrian = BridgingbpjsHelper::post_url(
                'antrean',
                'antrean/updatewaktu',
                $data
            );

            // kalau sukses baru buat log
            if ($updatewaktuantrian) {
                Bpjs_http_respon::create([
                    'noreg' => $noreg,
                    'method' => 'POST',
                    'request' => $data,
                    'respon' => $updatewaktuantrian,
                    'url' => 'antrean/updatewaktu',
                    'tgl' => $tgltobpjshttpres
                ]);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Timeout atau koneksi gagal
            Log::error('BPJS timeout: ' . $e->getMessage());
        } catch (\Throwable $e) {
            // Error lain
            Log::error('BPJS update waktu gagal: ' . $e->getMessage() . ' noreg ' . $noreg);
        }
    }
}
