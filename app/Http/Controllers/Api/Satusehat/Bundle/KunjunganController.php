<?php

namespace App\Http\Controllers\Api\Satusehat\Bundle;

use App\Helpers\AuthSatsetHelper;
use App\Helpers\BridgingSatsetHelper;
use App\Helpers\PostKunjunganHelper;
use App\Helpers\Satsets\PostKunjunganRajalHelper;
use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KunjunganController extends Controller
{
    public function index()
    {
        $jenis_kunjungan = request('jenis');
        if ($jenis_kunjungan === 'rajal') {
            // return self::rajal(request()->all());
            $arrayKunjungan = self::cekKunjunganRajal(request()->all());
            // $coba = $arrayKunjungan[0];
            // return self::rajal($coba);
            // echo count($arrayKunjungan);
            for ($i=0; $i < count($arrayKunjungan) ; $i++) { 
              self::rajal($arrayKunjungan[$i]);
              echo $i;
              // break;
              sleep(10);
            }
            return;
        }

        return new JsonResponse(['message' => 'Jenis Kunjungan Tidak Diketahui'], 500);
    }

    public static function cekKunjunganRajal($req)
    {
      $kemarin = Carbon::now()->subDay()->toDateString();
      $data = KunjunganPoli::select('rs1 as noreg')
        ->where('rs3', 'LIKE', '%' . $kemarin . '%')
        ->where('rs8', '!=', 'POL014')
        ->where('rs19', '=', '1') // kunjungan selesai
        ->orderBy('rs3', 'desc')
      ->get();
      $arr = collect($data)->map(function ($x) {
        return $x->noreg;
      });

      return $arr->toArray();
    }

    public static function rajal($noreg)
    {
        // return $req;
        
        // $kemarin = Carbon::now()->subDay()->toDateString();
        

        $data = KunjunganPoli::select(
            'rs17.rs1',
            'rs17.rs9',
            'rs17.rs4',
            'rs17.rs8',
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
            'rs17.rs19 as status',
            'rs15.satset_uuid as pasien_uuid',
            // 'satsets.uuid as satset',
            // 'satset_error_respon.uuid as satset_error',
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            // ->leftjoin('satsets', 'satsets.uuid', '=', 'rs17.rs1') //satset
            // ->leftjoin('satset_error_respon', 'satset_error_respon.uuid', '=', 'rs17.rs1') //satset error

            ->where('rs17.rs1', $noreg)

            // ->whereBetween('rs17.rs3', [$tgl, $tglx])
            // ->where('rs17.rs8', $user->kdruangansim ?? '')
            // ->where('rs17.rs3', 'LIKE', '%' . $kemarin . '%')
            // ->where('rs17.rs8', '!=', 'POL014')
            // ->where('rs17.rs19', '=', '1') // kunjungan selesai

            // ->where('rs19.rs5', '=', '1')
            // ->where('rs19.rs4', '=', 'Poliklinik')
            // ->whereNull('satsets.uuid')

            // ->where(function ($query) {
            //     $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%') //pasien nama
            //         ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%') //pasien
            //         ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%') //KUNJUNGAN
            //         ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%') //KUNJUNGAN
            //         ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
            //         // ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
            // })

            ->with([
                'datasimpeg:nik,nama,kelamin,kdpegsimrs,kddpjp,satset_uuid',
                'relmpoli'=>function($q){
                  $q->select('rs1','kode_ruang','rs7 as nama')->with('ruang:kode,uraian,groupper,satset_uuid,departement_uuid');
                },
                //   // 1 (mulai waktu tunggu admisi),
                //   // 2 (akhir waktu tunggu admisi/mulai waktu layan admisi),
                //   // 3 (akhir waktu layan admisi/mulai waktu tunggu poli),
                //   // 4 (akhir waktu tunggu poli/mulai waktu layan poli),
                //   // 5 (akhir waktu layan poli/mulai waktu tunggu farmasi),
                //   // 6 (akhir waktu tunggu farmasi/mulai waktu layan farmasi membuat obat),
                //   // 7 (akhir waktu obat selesai dibuat),
                //   // 99 (tidak hadir/batal)
                'taskid' => function ($q) {
                    $q->select('noreg', 'taskid', 'waktu', 'created_at')
                        ->orderBy('taskid', 'ASC');
                },
                'diagnosa' => function ($d) {
                    $d->select('rs1','rs3','rs4','rs7','rs8');
                    $d->with('masterdiagnosa');
                },
            ])

            // ->orderby('rs17.rs3', 'ASC')
            // ->limit(1)
            // ->get();
            ->first();

        // return $data;
        return self::kirimKunjungan($data);
    }

    public static function getPasienByNikSatset($pasien)
    {
        // return $request->all();
        $nik = $pasien->nik;
        $norm = $pasien->norm;
        // if (!$nik) {
        //     return ['message' => 'failed'];
        // }

        // get data ke satset
        $token = AuthSatsetHelper::accessToken();
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
        return $send;
    }

    public static function kirimKunjungan($data)
    {

      $pasien_uuid = $data->pasien_uuid;
      if (!$pasien_uuid) {
        $getPasienFromSatset = self::getPasienByNikSatset($data);
        $pasien_uuid = $getPasienFromSatset['data']['uuid'];
      }
      // return $pasien_uuid;
        // if (!$request->datasimpeg) {
        //     return response()->json([
        //         'message' => 'Maaf ... Dokter Ini Belum Ada di Kepegawaian RS'
        //     ], 500);
        // }

        // $practitioner = $request->datasimpeg['satset_uuid'];
        // if (!$practitioner) {
        //     return response()->json([
        //         'message' => 'Maaf ... Dokter Ini Belum Terkoneksi Ke Satu Sehat'
        //     ], 500);
        // }
        // $patient = $request->pasien_uuid;
        // if (!$patient) {
        //     return response()->json([
        //         'message' => 'Maaf ... Pasien Ini Belum Terkoneksi Ke Satu Sehat'
        //     ], 500);
        // }
        // $poli = $request->relmpoli;
        // if (!$poli) {
        //     return response()->json([
        //         'message' => 'Maaf ... DATA POLI Belum Ada'
        //     ], 500);
        // }

        // $mappingruang = $poli['ruang'];
        // if (!$mappingruang) {
        //     return response()->json([
        //         'message' => 'Maaf ... Tidak Ada Mapping Ruangan di Poli'
        //     ], 500);
        // }
        // $diagnosa = $request->diagnosa;
        // if (!$diagnosa) {
        //     return response()->json([
        //         'message' => 'Maaf ... Tidak Ada Diagnosa Pada Kunjungan Ini'
        //     ], 500);
        // }
        // return $practitioner;
        // $form = PostKunjunganHelper::form($request);

        // $send = BridgingSatsetHelper::post_bundle($request->token, $form, $request->noreg);
        // return $send;

        $send = PostKunjunganRajalHelper::form($data, $pasien_uuid);
        if ($send['message'] === 'success') {
          $token = AuthSatsetHelper::accessToken();
          $send = BridgingSatsetHelper::post_bundle($token, $send['data'], $data->noreg);
        }
        return $send;
    }
}
