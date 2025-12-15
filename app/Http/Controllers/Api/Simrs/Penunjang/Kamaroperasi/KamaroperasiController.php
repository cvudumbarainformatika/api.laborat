<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi;

use App\Helpers\FormatingHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Kamaroperasi\Kamaroperasi;
use App\Models\Simrs\Penunjang\Kamaroperasi\PermintaanOperasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class KamaroperasiController extends Controller
{
    public function permintaanoperasi(Request $request)
    {
        DB::select('call nota_tindakan(@nomor)');
        $x = DB::table('rs1')->select('rs14')->get();
        $wew = $x[0]->rs14;
        if ($request->kodepoli === 'POL014') {
            $notapermintaanok = $request->nota ?? FormatingHelper::notatindakan($wew, '/POK-IG');
        } else {
            $notapermintaanok = $request->nota ?? FormatingHelper::notatindakan($wew, '/POK-RJ');
        }

        $userid = FormatingHelper::session_user();
        $requestoperasi = PermintaanOperasi::create(
            [
                'rs1' => $request->noreg,
                'rs2' => $notapermintaanok,
                'rs3' => date('Y-m-d H:i:s'),
                // ],
                // [
                'rs4' => $request->permintaan,
                'rs8' => $request->kodedokter,
                'rs9' => '1',
                'rs10' => $request->kodepoli,
                'rs11' => $userid['kodesimrs'],
                'rs13' => $request->kodepoli,
                'rs14' => $request->kodesistembayar
            ]
        );

        if (!$requestoperasi) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }

        $nota = PermintaanOperasi::select('rs2 as nota')->where('rs1', $request->noreg)
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();

        return new JsonResponse(
            [
                'message' => 'Permintaan Berhasil Dikirim Ke OK',
                'result' => $requestoperasi,
                'nota' => $nota
            ],
            200
        );
    }

    public function getnota()
    {
        $nota = PermintaanOperasi::select('rs2 as nota')->where('rs1', request('noreg'))
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }

    public function hapuspermintaanok(Request $request)
    {
        $cari = PermintaanOperasi::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }
        // $hapusdetail = PermintaanOperasi::where('rs2', '=', $cari->nota)->delete();
        $hapus = $cari->delete();
        $nota = PermintaanOperasi::select('rs2 as nota')->where('rs1', $request->noreg)
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    }

    public function listkamaroperasi()
    {
        if (request('to') === '' || request('from') === null) {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tgl = request('to') . ' 00:00:00';
            $tglx = request('from') . ' 23:59:59';
        }
        $req = [
            'per_page' => request('per_page') ?? 25,
        ];
        $status = request('status') ?? '';
        $listkamaroperasi = PermintaanOperasi::query()
            ->select(
                'rs200.*',
                'rs200.rs1 as noreg',
                'rs200.rs14 as kodesistembayar',
                'rs200.rs8 as kodedokter',
                'rs200.rs10 as kodepoli', // untuk ruangan template
                'rs200.rs10 as kdruangan', // untuk ruangan resep    
                DB::raw('coalesce(pasien17.rs17, pasien23.rs17) as kelamin'),
                DB::raw('( CASE WHEN rs17.rs2 IS NOT NULL THEN rs17.rs2 ELSE rs23.rs2 END ) as norm'),
                'rs9.groups as groups',
                'rs9.rs2 as nama_sistembayar',
                DB::raw(
                    'coalesce(
                            concat(TIMESTAMPDIFF(YEAR, pasien17.rs16, CURDATE())," Tahun ",
                            TIMESTAMPDIFF(MONTH, pasien17.rs16, CURDATE()) % 12," Bulan ",
                            TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, pasien17.rs16, CURDATE()), pasien17.rs16), CURDATE()), " Hari"),
                            concat(TIMESTAMPDIFF(YEAR, pasien23.rs16, CURDATE())," Tahun ",
                            TIMESTAMPDIFF(MONTH, pasien23.rs16, CURDATE()) % 12," Bulan ",
                            TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, pasien23.rs16, CURDATE()), pasien23.rs16), CURDATE()), " Hari")
                            )
                            AS usia'
                ),
                DB::raw(
                    'coalesce(
                            concat(pasien17.rs3," ",pasien17.gelardepan," ",pasien17.rs2," ",pasien17.gelarbelakang), 
                            concat(pasien23.rs3," ",pasien23.gelardepan," ",pasien23.rs2," ",pasien23.gelarbelakang)
                            ) as nama'
                ),
                DB::raw(
                    'coalesce(
                            concat(pasien17.rs4," KEL ",pasien17.rs5," RT ",pasien17.rs7," RW ",pasien17.rs8," ",pasien17.rs6," ",pasien17.rs11," ",pasien17.rs10),
                            concat(pasien23.rs4," KEL ",pasien23.rs5," RT ",pasien23.rs7," RW ",pasien23.rs8," ",pasien23.rs6," ",pasien23.rs11," ",pasien23.rs10)
                            ) as alamat'
                ),
                'rs21.rs2 as nama_dokter',
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
                DB::raw(
                    '(  
                        CASE 
                            WHEN rs19.rs4 IS NOT NULL THEN rs19.rs2 ELSE rs24.rs2    
                        END
                    ) as ruangan'
                ),
                DB::raw(
                    '( 
                        CASE 
                            WHEN rs19.rs4 IS NOT NULL THEN rs19.rs2 ELSE rs24.rs2    
                        END
                    ) as poli'
                ),
                DB::raw(
                    '( 
                        CASE 
                            WHEN rs200.rs10 = "POL14" THEN rs17.rs3 ELSE rs23.rs3    
                        END
                    ) as tgl_mrs'
                ),

            )->leftjoin('rs17', 'rs17.rs1', '=', 'rs200.rs1') //rajal
            ->leftjoin('rs23', 'rs23.rs1', '=', 'rs200.rs1') //ranap
            ->leftjoin('rs15 as pasien17', 'pasien17.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs15 as pasien23', 'pasien23.rs1', '=', 'rs23.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs200.rs10') //poli
            ->leftjoin('rs24', 'rs24.rs1', '=', 'rs200.rs10') //ruangan ranap
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs200.rs8') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs200.rs14') //sistembayar
            ->where(function ($sts) use ($status) {
                if ($status !== 'all') {
                    if ($status === '') {
                        $sts->where('rs200.rs9', '!=', '1');
                    } else {
                        $sts->where('rs200.rs9', '=', $status);
                    }
                }
            })
            ->where(function ($x) {
                $x->where('rs200.rs1', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs200.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere(DB::raw('coalesce(pasien17.rs46, pasien23.rs46)'), 'LIKE', '%' . request('q') . '%')
                    ->orWhere(DB::raw('coalesce(pasien17.rs2, pasien23.rs2)'), 'LIKE', '%' . request('q') . '%')
                    ->orWhere(DB::raw('coalesce(pasien17.rs1, pasien23.rs1)'), 'LIKE', '%' . request('q') . '%');
            })
            ->whereBetween('rs200.rs3', [$tgl, $tglx]);
        // ->with(
        //     [
        //         'kunjunganranap.masterpasien',
        //         'kunjunganrajal.masterpasien',
        //         'sistembayar',
        //         'dokter',
        //         'kunjunganranap.relmasterruangranap',
        //         'kunjunganrajal.relmpoli',
        //         'permintaanobatoperasi' => function ($permintaanobatoperasi) {
        //             $permintaanobatoperasi->with([
        //                 'rinci' => function ($rinci) {
        //                     $rinci->with([
        //                         'obat:kd_obat,nama_obat'
        //                     ])
        //                         ->orderBy('id', 'ASC');
        //                 }
        //             ])
        //                 ->whereIn('flag', ['', '1', '2', '3', '4'])
        //                 ->orderBy('id', 'DESC');
        //         },
        //         'newapotekrajal' => function ($newapotekrajal) {
        //             $newapotekrajal->with([
        //                 'permintaanresep.mobat:kd_obat,nama_obat',
        //                 'permintaanracikan.mobat:kd_obat,nama_obat',
        //             ])
        //                 ->whereIn('flag', ['', '1', '2', '3', '4'])
        //                 ->orderBy('id', 'DESC');
        //         },
        //     ]
        // );
        $totalCount = (clone $listkamaroperasi)->count();
        $data = $listkamaroperasi->simplePaginate($req['per_page']);
        // ->paginate(request('per_page'));

        $resp = ResponseHelper::responseGetSimplePaginate($data, $req, $totalCount);
        return new JsonResponse($resp);
        // return new JsonResponse($listkamaroperasi);
    }
    public function bukaLayanan(Request $request)
    {
        $data = PermintaanOperasi::where('rs1', $request->noreg)
            ->with([
                'kunjunganranap.masterpasien',
                'kunjunganrajal.masterpasien',
                'sistembayar:rs1,rs2,rs9,groups',
                'dokter:nama,kdpegsimrs',
                'kunjunganranap.relmasterruangranap',
                'kunjunganrajal.relmpoli',
                'permintaanobatoperasi' => function ($permintaanobatoperasi) {
                    $permintaanobatoperasi->with([
                        'rinci' => function ($rinci) {
                            $rinci->with([
                                'obat:kd_obat,nama_obat'
                            ])
                                ->orderBy('id', 'ASC');
                        }
                    ])
                        ->whereIn('flag', ['', '1', '2', '3', '4'])
                        ->orderBy('id', 'DESC');
                },
                'newapotekrajal' => function ($newapotekrajal) {
                    $newapotekrajal->with([
                        'permintaanresep.mobat:kd_obat,nama_obat',
                        'permintaanracikan.mobat:kd_obat,nama_obat',
                    ])
                        ->whereIn('flag', ['', '1', '2', '3', '4'])
                        ->orderBy('id', 'DESC');
                },
            ])
            ->first();
        return new JsonResponse([
            'data' => $data
        ]);
    }
}
