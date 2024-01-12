<?php

namespace App\Http\Controllers\Api\Satusehat;

use App\Helpers\BridgingSatsetHelper;
use App\Helpers\PostKunjunganHelper;
use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\Pegawai\Extra;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use function PHPUnit\Framework\isEmpty;

class KunjunganSatsetController extends Controller
{
    public function listKunjungan()
    {
        $jenis_kunjungan = request('jenis');
        if ($jenis_kunjungan === 'rajal') {
            return self::rajal(request()->all());
        }
    }

    public static function rajal($req)
    {
        // return $req;
        if (!isset($req['to'])) {
            $tgl = Carbon::now()->subDay()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->subDay()->format('Y-m-d 23:59:59');
        } else {
            $tgl = $req->to . ' 00:00:00';
            $tglx = $req->from . ' 23:59:59';
        }

        $data = KunjunganPoli::select(
            'rs17.rs9',
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tgl_kunjungan',
            'rs17.rs8 as kodepoli',
            'rs19.rs2 as poli',
            'rs17.rs9 as kodedokter',
            'rs21.rs2 as dokter',
            'rs17.rs14 as kodesistembayar',
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups',
            'rs15.rs2 as nama',
            'rs15.rs49 as nik',
            'rs15.satset_uuid as satset_uuid',
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar

            ->whereBetween('rs17.rs3', [$tgl, $tglx])
            // ->where('rs17.rs8', $user->kdruangansim ?? '')
            ->where('rs17.rs8', '!=', 'POL014')

            ->with([
                'datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp,satset_uuid'
            ])


            ->orderby('rs17.rs3', 'ASC')
            ->paginate($req['per_page']);

        return response()->json($data);
    }

    public function getPasienByNikSatset(Request $request)
    {
        $nik = $request->nik;
        $token = $request->token;
        $norm = $request->norm;
        if (!$nik) {
            return response()->json([
                'message' => 'Maaf ... Nik Pasien Tidak Ada di Database RS'
            ], 500);
        }

        // get data ke satset
        $params = '/Patient?identifier=https://fhir.kemkes.go.id/id/nik|' . $nik;

        $send = BridgingSatsetHelper::get_data($token, $params);

        $data = Pasien::where([
            ['rs49', $nik],
            ['rs1', $norm],
        ])->first();

        if ($send['message'] === 'success') {
            $data->satset_uuid = $send['data']['uuid'];
            $data->save();
        }
        return $data;
    }

    public function kirimKunjungan(Request $request)
    {
        if (!$request->datasimpeg) {
            return response()->json([
                'message' => 'Maaf ... Dokter Ini Belum Ada di Kepegawaian RS'
            ], 500);
        }

        $practitioner = $request->datasimpeg['satset_uuid'];
        if (!$practitioner) {
            return response()->json([
                'message' => 'Maaf ... Dokter Ini Belum Terkoneksi Ke Satu Sehat'
            ], 500);
        }
        // return $practitioner;
        return PostKunjunganHelper::form($request);
    }
}
