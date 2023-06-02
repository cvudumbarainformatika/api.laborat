<?php

namespace App\Http\Controllers\Api\Anjungan;

use App\Events\AnjunganEvent;
use App\Helpers\BookingHelper;
use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Antrean\Booking;
use App\Models\Antrean\Dokter;
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

use function PHPUnit\Framework\isNull;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        // $nomor = Booking::where()
        // $kodeBooking = self::kode_booking($request->pasienbaru);
        $kodeBooking = BookingHelper::kodeBooking($request->pasienbaru);

        $pasienjkn = $request->jenispasien === 'JKN';
        $pasienbaru = $request->pasienbaru === 1 ||  $request->pasienbaru === '1';
        $kodepoli = $request->kodepoli;


        $layanan = self::cari_layanan($pasienjkn, $pasienbaru, $kodepoli);

        if (!$layanan) {
            $msg = 'Maaf Layanan ini Belum Ada di RSUD MOHAMAD SALEH';
            $metadata = ['code' => 201, 'message' => $msg];
            $res['metadata'] = $metadata;
            return response()->json($res);
        }

        $id_layanan = $layanan->id_layanan;
        $kodelayanan = $layanan->kode;
        $kuotajkn = $layanan->kuotajkn;
        $kuotanonjkn = $layanan->kuotanonjkn;

        $angkaantrean = self::nomor_anteran($id_layanan) + 1;
        $nomorantrean = $kodelayanan . sprintf("%03s", $angkaantrean);

        $sisakuotajkn = $kuotajkn;
        $sisakuotanonjkn = $kuotanonjkn;

        $os = array("1", "2", "3", "AP0001");
        if (!in_array($id_layanan, $os)) {
            if ($pasienjkn) {
                // $sisakuotajkn = (int)$kuotajkn > 0 ? (int)$kuotajkn - (int)$angkaantrean : 0;

                if ($sisakuotajkn === 0) {
                    $msg = 'Maaf, Antrian Sudah Penuh';
                    $metadata = ['code' => 201, 'message' => $msg];
                    $res['metadata'] = $metadata;
                    return response()->json($res);
                }
            } else {
                // $sisakuotanonjkn = (int)$kuotanonjkn > 0 ? (int)$kuotanonjkn - (int)$angkaantrean : 0;
                if ($sisakuotanonjkn === 0) {
                    $msg = 'Maaf, Antrian Sudah Penuh';
                    $metadata = ['code' => 201, 'message' => $msg];
                    $res['metadata'] = $metadata;
                    return response()->json($res);
                }
            }
        }

        $date = Carbon::parse($request->tanggalperiksa);
        $dt = $date->addMinutes(10);
        $estimasidilayani = $dt->getPreciseTimestamp(3);
        // $toSecond = $estimasidilayani / 1000;
        // $coba = Carbon::createFromTimestamp($toSecond)->toDateTimeString();


        $dok = $request->dokter ?? false;
        $dokter = $dok ? Dokter::firstOrCreate(
            ['kodedokter' => $dok['kodedokter']],
            [
                'namadokter' => $dok['namadokter'],
                'kodesubspesialis' => $dok['kodesubspesialis'],
            ]
        ) : false;


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
                'dokter_id' => $dok ? $dokter->id : null,
                'tanggalperiksa' => $request->tanggalperiksa,
                'tgl_ambil' => $request->tgl_ambil,
                'nomorreferensi' => $request->nomorreferensi,
                'nomorantrean' => $nomorantrean,
                'angkaantrean' => $angkaantrean,
                'estimasidilayani' => $estimasidilayani,
                'sisakuotajkn' => $sisakuotajkn,
                'kuotajkn' => $kuotajkn,
                'sisakuotanonjkn' => $sisakuotanonjkn,
                'kuotanonjkn' => $kuotanonjkn,
                'keterangan' => 'Peserta harap hadir 30 menit lebih awal guna pencatatan administrasi',
            ]
        );

        // $hitungsisakuotajkn = self::sisaKuotaJkn();


        $metadata = ['code' => 200, 'message' => 'ok'];
        $result = [
            'booking' => $save,
            'layanan' => $layanan
        ];
        $res['metadata'] = $metadata;
        $res['result'] = $result;

        return response()->json($res);
    }

    public function cetak_antrean()
    {
        $date = new Carbon();
        // $toSecond = $estimasidilayani / 1000;
        $tgl_ambil = $date->toDateTimeString();
        // return response()->json($tgl_ambil);
        $booking = Booking::where('kodebooking', request('kodebooking'))->first();
        $pasienjkn = $booking->jenispasien === 'JKN';
        $pasienbaru = $booking->pasienbaru === 1;
        $kodepoli = $booking->kodepoli;

        $layanan_id = $booking->layanan_id;
        $sisakuotajkn = $booking->sisakuotajkn;
        $sisakuotanonjkn = $booking->sisakuotanonjkn;

        $os = array("1", "2", "3", "AP0001");
        if (!in_array($layanan_id, $os)) {
            if ($pasienjkn) {
                $sisakuotajkn = (int)$sisakuotajkn > 0 ? (int)$sisakuotajkn - 1 : 0;
            } else {
                $sisakuotanonjkn = (int)$sisakuotanonjkn > 0 ? (int)$sisakuotanonjkn - 1 : 0;
            }
        }

        $booking = Booking::where('kodebooking', request('kodebooking'))->first();

        // $upd = Booking::where('kodebooking', request('kodebooking'))
        //     ->update([
        //         'statuscetak' => 1,
        //         'sisakuotajkn' => $sisakuotajkn,
        //         'sisakuotanonjkn' => $sisakuotanonjkn,
        //         'tgl_ambil' =>  $tgl_ambil
        //     ]);

        $upd = $booking->update([
            'statuscetak' => 1,
            'sisakuotajkn' => $sisakuotajkn,
            'sisakuotanonjkn' => $sisakuotanonjkn,
            'tgl_ambil' =>  $tgl_ambil
        ]);

        if (!$upd) {
            return response()->json('Ada Kesalahan', 500);
        }

        // Add Antrean to Ws BPJS .. belum

        $message = array(
            'menu' => 'cetak-antrean',
            'data' => $booking
        );

        event(new AnjunganEvent($message));

        return response()->json('ok');
    }

    public static function cari_layanan($pasienjkn, $pasienbaru, $kodepoli)
    {
        // $data = null;
        if ($pasienjkn === false) { // jika pasien umum
            // $data = Layanan::where('kode_bpjs', $kodepoli)->first(); // umum lama
            $data = Layanan::where('id_layanan', '1')->first(); //umum baru
            // if ($pasienbaru) {
            //     $data = Layanan::where('id_layanan', '1')->first(); //umum baru
            // }
            return $data;
        } else { //jika pasien jkn
            if ($pasienbaru === false) { //jika pasien lama
                $data = Layanan::where('kode_bpjs', $kodepoli)->first();
                if (!$data) {
                    $data = Layanan::where('id_layanan', '2')->first();
                }
                return $data;
            } else {
                $data = Layanan::where('id_layanan', '2')->first();
                return $data;
            }
        }
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
