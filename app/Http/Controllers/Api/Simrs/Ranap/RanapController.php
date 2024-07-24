<?php

namespace App\Http\Controllers\Api\Simrs\Ranap;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RanapController extends Controller
{
    public function kunjunganpasien()
    {
        // return request()->all();
        $dokter = request('kddokter');

        if (request('to') === '' || request('from') === null) {
            $tgl = Carbon::now()->format('Y-m-d');
            $tglx = Carbon::now()->format('Y-m-d');
        } else {
            $tgl = request('to') ;
            $tglx = request('from');
        }

        $tanggal = $tgl. ' 23:59:59';
        $tanggalx = Carbon::now()->subDays(180)->format('Y-m-d'). ' 00:00:00';

        // return $tanggalx;

        $status = request('status') === 'Belum Pulang' ? [''] : ['2', '3'];
        $ruangan = request('koderuangan');
        $data = Kunjunganranap::select(
            'rs23.rs1',
            'rs23.rs1 as noreg',
            'rs23.rs2 as norm',
            'rs23.rs3 as tglmasuk',
            'rs23.rs4 as tglkeluar',
            'rs23.rs5 as kdruangan',
            'rs23.rs5 as kodepoli', // ini khusus resep jangan diganti .... memang namanya aneh kok ranap ada kodepoli? ya? jangan dihapus yaaa.....
            'rs23.rs6 as ketruangan',
            'rs23.rs7 as nomorbed',
            'rs23.rs10 as kddokter',
            'rs21.rs2 as dokter',
            'rs23.rs19 as kdsistembayar',
            'rs23.rs19 as kodesistembayar', // ini untuk farmasi
            'rs23.rs22 as status', // '' : BELUM PULANG | '2 ato 3' : PASIEN PULANG
            'rs15.rs2 as nama_panggil',
            DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
            DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                        TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                        TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
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
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups',
            'rs21.rs2 as namanakes',
            'rs227.rs8 as sep',
            'rs227.kodedokterdpjp as kodedokterdpjp',
            'rs227.dokterdpjp as dokterdpjp',
            'rs24.rs2 as ruangan',
            'rs24.rs5 as group_ruangan'
        )
            ->leftjoin('rs15', 'rs15.rs1', 'rs23.rs2')
            ->leftjoin('rs9', 'rs9.rs1', 'rs23.rs19')
            ->leftjoin('rs21', 'rs21.rs1', 'rs23.rs10')
            ->leftjoin('rs227', 'rs227.rs1', 'rs23.rs1')
            ->leftjoin('rs24', 'rs24.rs1', 'rs23.rs5')
            ->where(function($query) use ($tanggal, $tanggalx) {
                $query->whereBetween('rs23.rs3', [$tanggalx, $tanggal]);
            })
            // ->whereDate('rs23.rs3', '<=', $tgl)
            // ->whereIn('rs23.rs22', $status)

            // ->where(function ($x) {
            //     $x->orWhereNull('dokterdpjp');
            // })

            ->where(function ($query) use ($ruangan) {
                $query->where(function ($query) use ($ruangan) {
                    for ($i = 0; $i < count($ruangan); $i++) {
                        $query->orwhere('rs23.rs5', 'like',  '%' . $ruangan[$i] . '%');
                    }
                });
            })



            ->where(function ($query) {
                $query->when(request('q'), function ($q) {
                    $q->where('rs23.rs1', 'like',  '%' . request('q') . '%')
                        ->orWhere('rs23.rs2', 'like',  '%' . request('q') . '%')
                        ->orWhere('rs15.rs2', 'like',  '%' . request('q') . '%');
                });
            })


            ->where(function ($q) use ($status) {
                $q->whereIn('rs23.rs22', $status);
            })
            // ->with([
            //     'newapotekrajal' => function ($newapotekrajal) {
            //         $newapotekrajal->with([
            //             'dokter:nama,kdpegsimrs',
            //             'permintaanresep.mobat:kd_obat,nama_obat',
            //             'permintaanracikan.mobat:kd_obat,nama_obat',
            //         ])
            //             ->orderBy('id', 'DESC');
            //     },
            //     'diagnosa' // ini sementara berhubungan dengan resep
            // ])
            // ->whereIn('rs23.rs5', $ruangan)
            // ->where('rs23.rs10', 'like', '%' . $dokter . '%')
            // ->where(function ($sts) use ($status) {
            //     if ($status !== 'all') {
            //         if ($status === '') {
            //             $sts->where('rs23.rs22', '!=', '1');
            //         } else {
            //             $sts->where('rs23.rs22', '=', $status);
            //         }
            //     }
            // })
            // ->where(function ($query) {
            //     $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs23.rs2', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs23.rs1', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs24.rs2', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs227.rs8', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
            // })
            ->orderby('rs23.rs3', 'ASC')
            // ->groupBy('rs23.rs1')
            ->paginate(20);

        return new JsonResponse($data);
    }

    public function bukalayanan(Request $request)
    {
        $cekx = Kunjunganranap::query()
        ->select(
            'rs1',
            'rs1 as noreg',
            'rs2 as norm')
            ->where('rs1', '=', $request->noreg)
            ->with([
                'newapotekrajal' => function ($q) {
                    $q->with([
                        'dokter:nama,kdpegsimrs',
                        'permintaanresep.mobat:kd_obat,nama_obat',
                        'permintaanracikan.mobat:kd_obat,nama_obat',
                        'sistembayar'
                    ])
                        ->orderBy('id', 'DESC');
                },
                'diagnosa' // ini berhubungan dengan resep
            ])->first();

        if (!$cekx) {
           return new JsonResponse([
               'message' => 'Data tidak ditemukan',
           ], 500);
        }

        return new JsonResponse($cekx);
    }
}
