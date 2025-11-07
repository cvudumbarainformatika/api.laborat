<?php

namespace App\Http\Controllers\Api\Simrs\Rehabmedik;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Fisioterapi\Fisioterapipermintaan;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengunjungController extends Controller
{
    public function index()
    {
      $total = self::query_table()->get()->count();
      $data = self::query_table()->simplePaginate(request('per_page'));

      $response = (object)[
        'total' => $total,
        'data' => $data
      ];

      return response()->json($response);
    }

    public function query_table_new()
    {
        if (request('to') === '' || request('from') === null) {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tgl = request('to') . ' 00:00:00';
            $tglx = request('from') . ' 23:59:59';
        }

        $sort = request('sort') === 'terbaru' ? 'DESC' : 'ASC';
        $status = request('status') ?? 'Semua';

        $query = KunjunganPoli::query();

        $select = $query->select(
            DB::raw('COALESCE(rs17.rs1, rs201.rs1) as noreg'),
            DB::raw('(CASE WHEN rs17.rs2 IS NOT NULL THEN rs17.rs2 WHEN rs23.rs2 IS NOT NULL THEN rs23.rs2 ELSE rs201.rs2 END) as norm'),
            DB::raw('COALESCE(rs17.rs3, rs201.rs3) as tgl_kunjungan'),
            DB::raw('COALESCE(rs17.rs8, rs201.rs10) as kdruangan'),
            DB::raw('COALESCE(rs17.rs8, rs201.rs10) as koderuangan'),
            DB::raw('COALESCE(rs17.rs8, rs201.rs10) as kodepoli'),
            DB::raw('COALESCE(rs17.rs14, rs23.rs19, rs201.rs14) as kodesistembayar'),
            DB::raw('(CASE 
                        WHEN rs201.rs9 = "2" THEN "1" 
                        WHEN rs201.rs9 = "1" THEN "" 
                        ELSE rs17.rs19 
                    END) as status'),

            DB::raw('COALESCE(
                concat(pasien17.rs3," ",pasien17.gelardepan," ",pasien17.rs2," ",pasien17.gelarbelakang),
                concat(pasien23.rs3," ",pasien23.gelardepan," ",pasien23.rs2," ",pasien23.gelarbelakang),
                concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang)
            ) as nama'),

            DB::raw('COALESCE(
                concat(pasien17.rs4," KEL ",pasien17.rs5," RT ",pasien17.rs7," RW ",pasien17.rs8," ",pasien17.rs6," ",pasien17.rs11," ",pasien17.rs10),
                concat(pasien23.rs4," KEL ",pasien23.rs5," RT ",pasien23.rs7," RW ",pasien23.rs8," ",pasien23.rs6," ",pasien23.rs11," ",pasien23.rs10),
                concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10)
            ) as alamat'),

            DB::raw('COALESCE(
                concat(TIMESTAMPDIFF(YEAR, pasien17.rs16, CURDATE())," Tahun ",
                TIMESTAMPDIFF(MONTH, pasien17.rs16, CURDATE()) % 12," Bulan ",
                TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, pasien17.rs16, CURDATE()), pasien17.rs16), CURDATE()), " Hari"),
                concat(TIMESTAMPDIFF(YEAR, pasien23.rs16, CURDATE())," Tahun ",
                TIMESTAMPDIFF(MONTH, pasien23.rs16, CURDATE()) % 12," Bulan ",
                TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, pasien23.rs16, CURDATE()), pasien23.rs16), CURDATE()), " Hari"),
                concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari")
            ) as usia'),

            DB::raw('COALESCE(pasien17.rs2, pasien23.rs2, rs15.rs2) as nama_panggil'),
            DB::raw('COALESCE(pasien17.rs16, pasien23.rs16, rs15.rs16) as tgllahir'),
            DB::raw('COALESCE(pasien17.rs17, pasien23.rs17, rs15.rs17) as kelamin'),
            DB::raw('COALESCE(pasien17.rs19, pasien23.rs18, rs15.rs19) as pendidikan'),
            DB::raw('COALESCE(pasien17.rs22, pasien23.rs22, rs15.rs22) as agama'),
            DB::raw('COALESCE(pasien17.rs37, pasien23.rs37, rs15.rs37) as templahir'),
            DB::raw('COALESCE(pasien17.rs39, pasien23.rs39, rs15.rs39) as suku'),
            DB::raw('COALESCE(pasien17.rs40, pasien23.rs40, rs15.rs40) as jenispasien'),
            DB::raw('COALESCE(pasien17.rs46, pasien23.rs46, rs15.rs46) as noka'),
            DB::raw('COALESCE(pasien17.rs49, pasien23.rs49, rs15.rs49) as noktp'),
            DB::raw('COALESCE(pasien17.rs55, pasien23.rs55, rs15.rs55) as nohp'),
            DB::raw('(CASE WHEN rs201.rs2 = "" THEN NULL ELSE rs201.rs2 END) as nota_permintaan'),
            DB::raw('(CASE WHEN rs19.rs1 IS NOT NULL THEN "rjl" ELSE "rnp" END) as flagdepo'),
            DB::raw('COALESCE(rs19.rs2, rs24.rs2) as ruangan'),
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups'
        )
        ->leftJoin('rs201', 'rs17.rs1', '=', 'rs201.rs1')
        ->leftJoin('rs23', 'rs201.rs1', '=', 'rs23.rs1')
        ->leftJoin('rs15', 'rs15.rs1', '=', 'rs17.rs2')
        ->leftJoin('rs15 as pasien17', 'pasien17.rs1', '=', 'rs17.rs2')
        ->leftJoin('rs15 as pasien23', 'pasien23.rs1', '=', 'rs23.rs2')
        ->leftJoin('rs24', 'rs24.rs1', '=', 'rs201.rs10')
        ->leftJoin('rs19', 'rs19.rs1', '=', 'COALESCE(rs17.rs8, rs201.rs10)')
        ->leftJoin('rs9', 'rs9.rs1', '=', 'COALESCE(rs17.rs14, rs23.rs19, rs201.rs14)');

        $q = $select
            ->whereBetween(DB::raw('COALESCE(rs17.rs3, rs201.rs3)'), [$tgl, $tglx])
            ->where(function ($sts) use ($status) {
                if ($status !== 'Semua') {
                    if ($status === 'Terlayani') {
                        $sts->where(function ($w) {
                            $w->where('rs17.rs19', '=', '1')
                            ->orWhere('rs201.rs9', '=', '2');
                        });
                    } else {
                        $sts->where(function ($w) {
                            $w->where('rs17.rs19', '=', '')
                            ->orWhere('rs201.rs9', '=', '1');
                        });
                    }
                }
            })
            ->where(function ($query) {
                $q = request('q');
                $query->where('rs17.rs1', 'LIKE', "%{$q}%")
                    ->orWhere('rs17.rs2', 'LIKE', "%{$q}%")
                    ->orWhere('rs15.rs46', 'LIKE', "%{$q}%")
                    ->orWhere('rs15.rs2', 'LIKE', "%{$q}%")
                    ->orWhere('rs201.rs1', 'LIKE', "%{$q}%")
                    ->orWhere('rs201.rs2', 'LIKE', "%{$q}%")
                    ->orWhere('pasien17.rs2', 'LIKE', "%{$q}%")
                    ->orWhere('pasien23.rs2', 'LIKE', "%{$q}%");
            })
            ->orderBy(DB::raw('COALESCE(rs17.rs3, rs201.rs3)'), $sort);

        return $q;
    }


    public function query_table()
    {
      if (request('to') === '' || request('from') === null) {
          $tgl = Carbon::now()->format('Y-m-d 00:00:00');
          $tglx = Carbon::now()->format('Y-m-d 23:59:59');
      } else {
          $tgl = request('to') . ' 00:00:00';
          $tglx = request('from') . ' 23:59:59';
      }

      $sort = request('sort') === 'terbaru'? 'DESC':'ASC';
      $status = request('status') ?? 'Semua';

      $permintaan = self::permintaanFisio($tgl, $tglx, $sort, $status);

      $query = KunjunganPoli::query();

      $select = $query->select(
        'rs17.rs1 as noreg',
        'rs17.rs2 as norm',
        'rs17.rs3 as tgl_kunjungan',
        'rs17.rs8 as kdruangan',
        'rs17.rs8 as koderuangan',
        'rs17.rs8 as kdgroup_ruangan',
        'rs17.rs8 as kodepoli',
        'rs17.rs14 as kodesistembayar',
        'rs17.rs19 as status',

        DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
        DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
        DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
        'rs15.rs2 as nama_panggil',
        'rs15.rs16 as tgllahir',
        'rs15.rs17 as kelamin',
        'rs15.rs19 as pendidikan',
        'rs15.rs22 as agama',
        'rs15.rs37 as templahir',
        'rs15.rs39 as suku',
        'rs15.rs40 as jenispasien',
        'rs15.rs46 as noka',
        'rs15.rs49 as noktp',
        'rs15.rs55 as nohp',
        // 'permintaan.rs2 as nota_permintaan',
        DB::raw('(CASE WHEN permintaan.rs2 ="" THEN NULL ELSE permintaan.rs2 END) as nota_permintaan'),
        DB::raw('(CASE WHEN rs17.rs8 ="" THEN "rjl" ELSE "rjl" END) as flagdepo'),
        'rs19.rs2 as ruangan',
        'rs9.rs2 as sistembayar',
        'rs9.groups as groups',
      )
        ->leftjoin('rs201 as permintaan', 'rs17.rs1', '=', 'permintaan.rs1') //permintaan
        ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
        ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
        ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
        ;

        $q = $select
            ->whereBetween('rs17.rs3', [$tgl, $tglx])
            ->where('rs17.rs8', '=', 'PEN004')
            ->where(function ($sts) use ($status) {
                if ($status !== 'Semua') {
                    if ($status === 'Terlayani') {
                        $sts->where('rs17.rs19', '=','1');
                    } else {
                        $sts->where('rs17.rs19', '=', '');
                    }
                }
            })
            ->where(function ($query) {
                $query->where('rs17.rs1', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs15.rs2', 'LIKE', '%' . request('q') . '%')
                    ;
            })

            ->union($permintaan)
            // ->groupBy('rs17.rs1')
            ;
        $result = $q
        ;

        return $result;

    }

    static function permintaanFisio($tgl, $tglx, $sort, $status)
    {
        $data = Fisioterapipermintaan::query();
        $select = $data->select(
        'rs201.rs1 as noreg',
        // 'rs201.rs2 as norm',
        DB::raw('( CASE WHEN rs17.rs2 IS NOT NULL THEN rs17.rs2 ELSE rs23.rs2 END ) as norm'),
        'rs201.rs3 as tgl_kunjungan',
        'rs201.rs10 as kdruangan',
        'rs201.rs10 as koderuangan',
        'rs201.rs10 as kodepoli',
        'rs24.rs4 as kdgroup_ruangan',
        DB::raw('coalesce(rs17.rs14, rs23.rs19) as kodesistembayar'),
        // DB::raw('coalesce(rs17.rs19, rs23.rs22) as status'),
        // 'rs201.rs9 as status',
        DB::raw('CASE WHEN rs201.rs9 = "2" THEN "1" ELSE "" END as status'),

        DB::raw('coalesce(
          concat(pasien17.rs3," ",pasien17.gelardepan," ",pasien17.rs2," ",pasien17.gelarbelakang), 
          concat(pasien23.rs3," ",pasien23.gelardepan," ",pasien23.rs2," ",pasien23.gelarbelakang)
        ) as nama'
      ),
        DB::raw('coalesce(
          concat(pasien17.rs4," KEL ",pasien17.rs5," RT ",pasien17.rs7," RW ",pasien17.rs8," ",pasien17.rs6," ",pasien17.rs11," ",pasien17.rs10),
          concat(pasien23.rs4," KEL ",pasien23.rs5," RT ",pasien23.rs7," RW ",pasien23.rs8," ",pasien23.rs6," ",pasien23.rs11," ",pasien23.rs10)
        )
        as alamat'),
        DB::raw('coalesce(
          concat(TIMESTAMPDIFF(YEAR, pasien17.rs16, CURDATE())," Tahun ",
          TIMESTAMPDIFF(MONTH, pasien17.rs16, CURDATE()) % 12," Bulan ",
          TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, pasien17.rs16, CURDATE()), pasien17.rs16), CURDATE()), " Hari"),
          concat(TIMESTAMPDIFF(YEAR, pasien23.rs16, CURDATE())," Tahun ",
          TIMESTAMPDIFF(MONTH, pasien23.rs16, CURDATE()) % 12," Bulan ",
          TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, pasien23.rs16, CURDATE()), pasien23.rs16), CURDATE()), " Hari")
        )
        AS usia'),
        DB::raw('coalesce(pasien17.rs2, pasien23.rs2) as nama_panggil'),
        DB::raw('coalesce(pasien17.rs16, pasien23.rs16) as tgllahir'),
        DB::raw('coalesce(pasien17.rs17, pasien23.rs17) as kelamin'),
        DB::raw('coalesce(pasien17.rs18, pasien23.rs18) as pendidikan'),
        DB::raw('coalesce(pasien17.rs22, pasien23.rs22) as agama'),
        DB::raw('coalesce(pasien17.rs37, pasien23.rs37) as templahir'),
        DB::raw('coalesce(pasien17.rs39, pasien23.rs39) as suku'),
        DB::raw('coalesce(pasien17.rs40, pasien23.rs40) as jenispasien'),
        DB::raw('coalesce(pasien17.rs46, pasien23.rs46) as noka'),
        DB::raw('coalesce(pasien17.rs49, pasien23.rs49) as noktp'),
        DB::raw('coalesce(pasien17.rs55, pasien23.rs55) as nohp'),
        DB::raw('(CASE WHEN rs201.rs2 ="" THEN NULL ELSE rs201.rs2 END) as nota_permintaan'),
        DB::raw('(CASE WHEN rs19.rs1 IS NOT NULL THEN "rjl" ELSE "rnp" END) as flagdepo'),
        DB::raw('coalesce(rs19.rs2, rs24.rs2) as ruangan'),
        'rs9.rs2 as sistembayar',
        'rs9.groups as groups',
        )
        ->leftjoin('rs17', 'rs201.rs1', '=', 'rs17.rs1') //rajal
        ->leftjoin('rs23', 'rs201.rs1', '=', 'rs23.rs1') //ranap
        ->leftjoin('rs24', 'rs24.rs1', '=', 'rs201.rs10') //ruangan ranap
        // ->leftjoin('rs15 as pasien', 'rs15.rs1', '=', 'rs201.rs2') //pasien
        ->leftjoin('rs15 as pasien17', 'pasien17.rs1', '=', 'rs17.rs2') //pasien
        ->leftjoin('rs15 as pasien23', 'pasien23.rs1', '=', 'rs23.rs2') //pasien
        ->leftjoin('rs19', 'rs19.rs1', '=', 'rs201.rs10') //poli
        ->leftjoin('rs9', 'rs9.rs1', '=', 'rs201.rs14') //sistembayar
        ;

        $q = $select
            ->whereBetween('rs201.rs3', [$tgl, $tglx])
            ->where('rs201.rs2', '!=', '')
            ->whereNotNull('rs201.rs2')
            // ->whereNull('rs201.rs13')
            ->where(function ($sts) use ($status) {
                if ($status !== 'Semua') {
                    if ($status === 'Terlayani') {
                        $sts->where('rs201.rs9', '=','2');
                    } else {
                        $sts->where('rs201.rs9', '=', '1');
                    }
                }
            })
            ->where(function ($query) {
                $query->where('rs201.rs1', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs201.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('pasien17.rs46', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('pasien17.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('pasien17.rs1', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('pasien23.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('pasien23.rs1', 'LIKE', '%' . request('q') . '%')
                    ;
            })
            ->groupBy('rs201.rs2')
            ;
            // ->orderby('rs17.rs3', $sort);

        return $q;
    }




    public function terimapasien()
    {
       $cekx = Fisioterapipermintaan::query();
       $data = $cekx->select(
            'rs201.rs1', 
            'rs201.rs1 as noreg'
            )
            ->with([
                'diagnosa', // ini berhubungan dengan resep
                // 'newapotekrajal' => function ($q) {
                //     $q->with([
                //         'dokter:nama,kdpegsimrs',
                //         'permintaanresep.mobat:kd_obat,nama_obat,bentuk_sediaan,satuan_k,jenis_perbekalan',
                //         'permintaanracikan.mobat:kd_obat,nama_obat,bentuk_sediaan,satuan_k,jenis_perbekalan',
                //         'sistembayar'
                //     ])
                //         ->orderBy('id', 'DESC');
                // },
                'transradiologi' => function ($transradiologi) {
                    $transradiologi->with('relmasterpemeriksaan');
                },
                'radiologi' => function ($t) {
                    $t->orderBy('id', 'DESC');
                    $t->with([
                        'rincians' => function ($r) {
                            $r->leftJoin('rs151', function ($join) {
                                $join->on('rs48.rs2', '=', 'rs151.rs5')
                                    ->on('rs48.rs1', '=', 'rs151.rs1')
                                    ->on('rs48.rs4', '=', 'rs151.kode');
                            })
                                ->select(
                                    'rs48.*',
                                    'rs151.hasil',
                                    'rs151.rs3 as kesimpulan',
                                    'rs151.rs4 as pelaksana'
                                );
                        },
                        'rincians.relmasterpemeriksaan',
                        'dokter:nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp,ttdpegawai',

                    ])->orderBy('rs106.id', 'DESC');
                },
                'hasilradiologi' => function ($t) {
                    $t->orderBy('id', 'DESC');
                },
                'laborats' => function ($t) {
                    $t->with('details.pemeriksaanlab')
                        ->orderBy('id', 'DESC');
                },
                'laboratold' => function ($t) {
                    $t->select('rs51.*', 'rs49.rs2 as pemeriksaan', 'rs49.rs21 as paket')->with('pemeriksaanlab')
                        ->leftjoin('rs49', 'rs49.rs1', 'rs51.rs4')
                        ->orderBy('id', 'DESC');
                },
            ])
            ->where('rs201.rs1', request('noreg'))
            ->first();
        
        if (!$data) {
            return new JsonResponse([
                'message' => 'Maaf ... Data Tidak Ditemukan',
            ], 500);
        }


        return new JsonResponse($data, 200);
    }
}
