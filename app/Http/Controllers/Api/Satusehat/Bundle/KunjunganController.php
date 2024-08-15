<?php

namespace App\Http\Controllers\Api\Satusehat\Bundle;

use App\Helpers\AuthSatsetHelper;
use App\Helpers\BridgingSatsetHelper;
use App\Helpers\PostKunjunganHelper;
use App\Helpers\Satsets\PostKunjunganRajalHelper;
use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KunjunganController extends Controller
{
    public function index()
    {
        $jenis_kunjungan = request('jenis');

        if ($jenis_kunjungan === 'rajal') {
            // return self::rajal(request()->all());
            $arrayKunjungan = self::cekKunjunganRajal(request()->all());
            for ($i=0; $i < count($arrayKunjungan) ; $i++) { 
              self::rajal($arrayKunjungan[$i]);
              echo $i;
              // break;
              sleep(5);//menunggu 5 detik
            }
            return;
        }

        if ($jenis_kunjungan === 'ranap') {
            $arrayKunjungan = self::cekKunjunganRanap();
            return self::ranap($arrayKunjungan[0]);
            // for ($i=0; $i < count($arrayKunjungan) ; $i++) { 
            //   self::ranap($arrayKunjungan[$i]);
            //   echo $i;
            //   sleep(5);//menunggu 5 detik
            // }
            // return;
        }

        return new JsonResponse(['message' => 'Jenis Kunjungan Tidak Diketahui'], 500);
    }


    // KUNJUNGAN RAJAL ==========================================================================================================
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


    // KUNJUNGAN RANAP ==========================================================================================================

    public static function cekKunjunganRanap()
    {
      // $kemarin = Carbon::now()->subDay()->toDateString();
      $lusa = Carbon::now()->subDays(2)->toDateString();
      // return $lusa;
      $data = Kunjunganranap::select('rs1 as noreg', 'rs4 as tgl_pulang')
        ->where('rs4', 'LIKE', '%' . $lusa . '%')
        ->whereIn('rs22', ['2', '3']) // kunjungan selesai
        ->orderBy('rs3', 'desc')
        ->get();
      // return $data;
      $arr = collect($data)->map(function ($x) {
        return $x->noreg;
      });

      return $arr->toArray();
    }

    public static function ranap($noreg)
    {
      $query = Kunjunganranap::query();
  
      $select = $query->select(
          'rs23.rs1',
          'rs23.rs1 as noreg',
          'rs23.rs2 as norm',
          'rs23.rs3 as tglmasuk',
          'rs23.rs4 as tglkeluar',
          'rs23.rs5 as kdruangan',
          'rs23.rs6 as ketruangan',
          'rs23.rs7 as nomorbed',
          'rs23.rs10 as kddokter',
          'rs21.rs2 as dokter',
          'rs23.rs19 as kodesistembayar', // ini untuk farmasi
          'rs23.rs22 as status', // '' : BELUM PULANG | '2 ato 3' : PASIEN PULANG
          'rs15.rs2 as nama_panggil',

          DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
          DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
          DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                      TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                      TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
          DB::raw("(IF(rs23.rs4='0000-00-00 00:00:00',datediff('".date("Y-m-d")."',rs23.rs3),
          datediff(rs23.rs4,rs23.rs3)))+1  as lama"),

          'rs15.rs4 as alamatbarcode',
          'rs15.rs16 as tgllahir',
          'rs15.rs17 as kelamin',
          'rs15.rs19 as pendidikan',
          'rs15.rs22 as agama',
          'rs15.rs37 as templahir',
          'rs15.rs39 as suku',
          'rs15.rs40 as jenispasien',
          'rs15.rs46 as noka',
          'rs15.rs49 as nktp',
          'rs15.rs55 as nohp',
          'rs15.satset_uuid as pasien_uuid',
          'rs9.rs2 as sistembayar',
          'rs9.groups as groups',
          'rs21.rs2 as namanakes',
          'rs222.rs8 as sep_igd',
          'rs227.rs8 as sep',
          'rs227.rs10 as faskesawal',
          'rs227.kodedokterdpjp as kodedokterdpjp',
          'rs227.dokterdpjp as dokterdpjp',
          'rs24.rs2 as ruangan',
          'rs24.rs3 as kelasruangan',
          'rs24.rs5 as group_ruangan',
          // 'rs101.rs3 as kode_diagnosa'
          // 'bpjs_spri.noSuratKontrol as noSpri'
      )
          ->leftjoin('rs15', 'rs15.rs1', 'rs23.rs2')
          ->leftjoin('rs9', 'rs9.rs1', 'rs23.rs19')
          ->leftjoin('rs21', 'rs21.rs1', 'rs23.rs10')
          ->leftjoin('rs24', 'rs24.rs1', 'rs23.rs5')
          ->leftjoin('rs227', 'rs227.rs1', 'rs23.rs1')
          ->leftjoin('rs222', 'rs222.rs1', 'rs23.rs1')
          // ->leftjoin('rs101', 'rs101.rs1', 'rs23.rs1')
          // ->leftjoin('bpjs_spri', 'rs23.rs1', '=', 'bpjs_spri.noreg')

          // ->with(['sepranap' => function($q) {
          //     $q->select('rs1', 'rs8 as noSep', 'rs3 as ruang', 'rs5 as noRujukan', 'rs7 as diagnosa', 'rs10 as ppkRujukan', 'rs11 as jenisPeserta');
          // }])

          ->where('rs23.rs1', $noreg)

          ->with([
            'diagnosa' => function($q) {
              $q->select('rs101.rs1', 'rs101.rs3 as kode', 'rs99x.rs4 as inggris', 'rs99x.rs3 as indonesia', 'rs101.rs4 as type', 'rs101.rs7 as status')
                  ->leftjoin('rs99x', 'rs101.rs3', 'rs99x.rs1')
                  ->orderBy('rs101.id', 'desc');
            },
            'datasimpeg:nik,nama,kelamin,kdpegsimrs,kddpjp,satset_uuid',
          
          ])
          
          ->where('rs23.rs1', $noreg)
          ->first();


      return self::kirimKunjunganRanap($select);
    }

    public function kirimKunjunganRanap($data)
    {
        $pasien_uuid = $data->pasien_uuid;
        if (!$pasien_uuid) {
          $getPasienFromSatset = self::getPasienByNikSatset($data);
          $pasien_uuid = $getPasienFromSatset['data']['uuid'];
        }

        
    }


}
