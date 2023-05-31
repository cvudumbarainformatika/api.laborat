<?php

namespace App\Http\Controllers\Api\Mjkn;

use App\Helpers\AuthjknHelper;
use App\Helpers\BridgingbpjsHelper;
use App\Helpers\DateHelper;
use App\Http\Controllers\Controller;
use App\Models\Antrean\Booking;
use App\Models\Antrean\Unit;
use App\Models\Pasien;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Bpjs\BpjsPasienBaru;
use App\Models\Simrs\Bpjs\Bpjsrefpoli;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AmbilAntreanController extends Controller
{
    public function byLayanan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomorkartu' => 'required',
            'nik' => 'required',
            'kodepoli' => 'required',
            'tanggalperiksa' => 'required',
            'kodedokter' => 'required',
            'jampraktek' => 'required',
            'jeniskunjungan' => 'required',
            'nomorreferensi' => 'required'
        ]);
        if ($validator->fails()) {
            $response = [
                'metadata' => [
                    'message' => $validator->errors()->first(),
                    'code' => 201,
                ]
            ];
            return response()->json($response, $response['metadata']['code']);
        }


        $noBpjs = $request->input('nomorkartu');
        $noKtp = $request->input('nik');
        $kdPoli = $request->input('kodepoli');
        $tanggalperiksa = $request->input('tanggalperiksa');
        $kodedokter = $request->input('kodedokter');


        // CARI POLI
        $caripoli = Bpjsrefpoli::getByKdSubspesialis($kdPoli)->get();

        if (count($caripoli) === 0) {
            return response()->json([
                'metadata' => [
                    'message' => 'Poli tidak ditemukan',
                    'code' => 201,
                ]
            ], 201);
        }

        $poli = $caripoli[0];
        $namapoli = $poli->nmsubspesialis;

        $AntrianUnit = Unit::where('layanan_id', $poli->kdpolirs)->first();

        if (!$AntrianUnit) {
            return response()->json([
                'metadata' => [
                    'message' => 'Unit Belum Ada',
                    'code' => 201,
                ]
            ], 201);
        }

        if ($AntrianUnit->tersedia == 'Tidak Ada')
            return response()->json([
                'metadata' => [
                    'message' => 'Maaf, antrian online tidak tersedia pada poli tujuan. Silahkan untuk melakukan antrian offline.',
                    'code' => 201,
                ]
            ], 201);


        $jadwalPoli = self::cari_dokter($kdPoli, $tanggalperiksa); // json_decode($jadwalPoli) for back to jSon
        // return new JsonResponse($jadwalPoli);
        $code = $jadwalPoli['metadata']['code'];
        if ($code != 200)
            return response()->json([
                'metadata' => [
                    'message' => 'Maaf, jadwal poli tujuan tidak ditemukan pada tanggal tersebut.',
                    'code' => 201,
                ]
            ], 201);

        $cekDokter = collect($jadwalPoli['result'])->firstWhere('kodedokter', $kodedokter);

        if (!$cekDokter)
            return response()->json([
                'metadata' => [
                    'message' => 'Maaf, jadwal dokter tujuan tidak ditemukan pada tanggal tersebut.',
                    'code' => 201,
                ]
            ], 201);

        $jamTutup = strtotime($tanggalperiksa . ' 10:59:59');
        $jamSekarang = strtotime(date('Y-m-d H:i:s'));
        $day = new Carbon();
        $hrIni = $day->toDateString();

        if ($tanggalperiksa === $hrIni && $jamSekarang > $jamTutup) {
            // if($tanggalperiksa == DateController::getDate()){
            $response = [
                'metadata' => [
                    'message' => 'Maaf antrian hari ini sudah tutup jam 11:00.',
                    'code' => 201,
                ]
            ];
            return response()->json($response, $response['metadata']['code']);
        }


        $maksimalHari = DateHelper::selisihHari($hrIni, $tanggalperiksa);
        if ($maksimalHari > 2) {
            $response = [
                'metadata' => [
                    'message' => 'Maaf antrian hanya bisa diambil maksimal 2 hari sebelum tanggal kunjungan.',
                    'code' => 201,
                ]
            ];
            return response()->json($response, $response['metadata']['code']);
        }


        // CARI PASIEN DI SIMRS

        $pasienGetByNoBpjs = Pasien::getByNoBpjs($noBpjs)->get();
        $pasienGetByNoKtp = Pasien::getByNik($noKtp)->get();
        $bpjsPasienGetByNoBpjs = BpjsPasienBaru::getByNoBpjs($noBpjs)->get();

        $layanan_id = $poli->kdpolirs;
        $keterangan = '-';
        $norm = '-';

        // CARI PASIEN DI WS BPJS
        // $cekBpjs = self::cari_dokter($noBpjs, $tanggalperiksa);


        if (count($pasienGetByNoBpjs) == 0) {
            $layanan_id = '2';
            $keterangan = 'Silahkan peserta menunggu panggilan antrian di pendaftaran.';
        } else {
            $usia = DateHelper::usia($pasienGetByNoBpjs[0]->rs16);
            $norm = $pasienGetByNoBpjs[0]->rs1;
            if ((int) $usia > 60) {
                $layanan_id = '3'; // LANSIA
                $keterangan = 'Silahkan peserta menunggu panggilan antrian di pendaftaran.';
            } else {
                $keterangan = 'Data pasien ini tidak ditemukan, silahkan Melakukan Registrasi Pasien Baru';
            }
        }


        // AMBIL NO ANTRIAN
        //...

        return new JsonResponse('ok');
    }

    public function cari_dokter($kodepoli, $tanggal)
    {
        return BridgingbpjsHelper::get_url('antrean', 'jadwaldokter/kodepoli/' . $kodepoli . "/tanggal/" . $tanggal);
    }
    public function cari_pasien($noka, $tglSekarang)
    {
        return BridgingbpjsHelper::get_url('vclaim', 'Peserta/nokartu/' . $noka . "/tglSEP/" . $tglSekarang);
    }
}
