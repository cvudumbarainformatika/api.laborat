<?php

namespace App\Http\Controllers\Api\Anjungan;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Antrean\Booking;
use App\Models\Antrean\Layanan;
use App\Models\Antrean\Unit;
use App\Models\KunjunganPoli;
use App\Models\Pasien;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        // $nomor = Booking::where()
        $kodeBooking = self::kode_booking($request->pasienbaru);

        $pasienjkn = $request->jenispasien === 'JKN';
        $pasienbaru = $request->pasienbaru === 1;
        $kodepoli = $request->kodepoli;


        $layanan = self::cari_layanan($pasienjkn, $pasienbaru, $kodepoli);
        $id_layanan = $layanan->id_layanan;
        $kodelayanan = $layanan->kode;

        $angkaantrean = self::nomor_anteran($id_layanan) + 1;
        $nomorantrean = $kodelayanan . sprintf("%03s", $angkaantrean);


        $date = Carbon::parse($request->tanggalperiksa);
        $dt = $date->addMinutes(10);
        $estimasidilayani = $dt->getPreciseTimestamp(3);
        // $toSecond = $estimasidilayani / 1000;
        // $coba = Carbon::createFromTimestamp($toSecond)->toDateTimeString();

        $save = Booking::create(
            [
                'kodebooking' => $kodeBooking,
                'jenispasien' => $request->jenispasien,
                'norm' => $request->norm,
                'namapasien' => $request->namapasien,
                'nomorkartu' => $request->nomorkartu,
                'nik' => $request->nik,
                'nohp' => $request->nohp,
                'kodepoli' => $request->kodepoli,
                'namapoli' => $request->namapoli,
                'pasienbaru' => $request->pasienbaru,
                'layanan_id' => $id_layanan,
                'jeniskunjungan' => $request->jeniskunjungan,
                // 'dokter_id' => $request->dokter_id,
                'tanggalperiksa' => $request->tanggalperiksa,
                'tgl_ambil' => $request->tgl_ambil,
                'nomorreferensi' => $request->nomorreferensi,
                'nomorantrean' => $nomorantrean,
                'angkaantrean' => $angkaantrean,
                'estimasidilayani' => $estimasidilayani,
                // 'sisakuotajkn' => $request->sisakuotajkn,
                // 'kuotajkn' => $request->kuotajkn,
                // 'sisakuotanonjkn' => $request->sisakuotanonjkn,
                // 'kuotanonjkn' => $request->kuotanonjkn,
                'keterangan' => 'Peserta harap hadir 30 menit lebih awal guna pencatatan administrasi',
            ]
        );


        return response()->json($save);
    }

    public static function cari_layanan($pasienjkn = true, $pasienbaru = true, $kodepoli)
    {
        $data = null;
        if (!$pasienjkn) { // jika pasien umum
            $data = Layanan::where('id_layanan', '1')->first();
        } else {
            if (!$pasienbaru) {
                $data = Layanan::where('kode_bpjs', $kodepoli)->first();
                if (!$data) {
                    $data = Layanan::where('id_layanan', '2')->first();
                }
            } else {
                $data = Layanan::where('id_layanan', '2')->first();
            }
        }
        return $data;
    }

    public static function kode_booking($pasienbaru)
    {
        $random = Str::random(2);
        $date = Carbon::parse(new DateTime());
        $kodeBooking = strtoupper($random) . $date->isoFormat('DDDDHHmmss') . 'P' . $pasienbaru;
        return $kodeBooking;
    }

    public static function nomor_anteran($id_layanan)
    {
        $date = Carbon::parse(new DateTime());
        $hrIni = $date->isoFormat('YYYY-MM-DD');

        $data = Booking::whereBetween('created_at', [$hrIni . ' 00:00:00', $hrIni . ' 23:59:59'])
            ->where('layanan_id', $id_layanan)
            ->count();

        return $data;
    }
}
