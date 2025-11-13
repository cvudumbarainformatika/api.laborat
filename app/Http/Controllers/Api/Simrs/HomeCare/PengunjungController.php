<?php

namespace App\Http\Controllers\Api\Simrs\HomeCare;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Homecare\HomeCareKunjungan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengunjungController extends Controller
{
    //
    public function listKunjungan()
    {
        $req = [
            'order_by' => request('order_by') ?? 'created_at',
            'q' => request('q') ?? null,
            'page' => request('page') ?? 1,
            'per_page' => request('per_page') ?? 10,
            'from' => request('from') ?? null,
            'to' => request('to') ?? null,
            'flag' => request('status') ?? null,

        ];

        $raw = HomeCareKunjungan::query()
            ->select(
                'home_care_kunjungans.*',
                'home_care_kunjungans.kode_poli as kodepoli',
                'home_care_kunjungans.kode_poli as kdruangan',
                'rs15.rs16 as tgllahir',
                'rs15.rs49 as nktp',
                'rs15.rs17 as kelamin',
                'rs19.rs2 as poli',
                'home_care_kunjungans.dpjp as kddokter',
                'home_care_kunjungans.dpjp as kodedokter',
                DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
                DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
                DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                        TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                        TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),

                'kepegx.pegawai.nama as dokter',
            )
            ->leftJoin('rs15', 'rs15.rs1', '=', 'home_care_kunjungans.norm')
            ->leftJoin('rs19', 'rs19.rs1', '=', 'home_care_kunjungans.kode_poli')
            ->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'home_care_kunjungans.dpjp')
            ->when($req['q'], function ($q) use ($req) {
                $q->where(function ($y) use ($req) {
                    $y->where('rs15.rs2', 'LIKE', '%' . $req['q'] . '%')
                        ->orWhere('rs15.rs1', 'LIKE', '%' . $req['q'] . '%')
                        ->orWhere('home_care_kunjungans.noreg', 'LIKE', '%' . $req['q'] . '%');
                });
            })
            ->when($req['flag'], function ($q) use ($req) {
                $flag = strtolower($req['flag']);

                if ($flag == 'dalam pelayanan') $q->where('flag', '1');
                else if ($flag == 'terlayani') $q->where('flag', '2');
                else if ($flag == 'belum terlayani') $q->where(function ($y) {
                    $y->whereNull('flag')->orWhere('flag', '');
                });
            })
            ->when(!empty($req['from']) && !empty($req['to']), function ($q) use ($req) {
                $q->whereBetween('tgl_kunjungan', [
                    $req['from'] . ' 00:00:00',
                    $req['to'] . ' 23:59:59',
                ]);
            });
        // ->with([
        // 'masterpasien:rs1,rs2,rs17,rs16 as tgllahir',
        // 'poli:rs1,rs2',
        // 'dokter:nama,kdpegsimrs',
        // ]);
        $totalCount = (clone $raw)->count();
        $data = $raw->simplePaginate($req['per_page']);
        // $data->append('usia');

        $resp = ResponseHelper::responseGetSimplePaginate($data, $req, $totalCount);
        return new JsonResponse($resp);
    }
    public function berangkat(Request $request)
    {

        $data = HomeCareKunjungan::find($request->id);
        if (!$data) return new JsonResponse(['message' => 'Data Kunjungan tidak ditemukan'], 410);
        $data->update(['tgl_berangkat' => Carbon::now()->format('Y-m-d H:i:s')]);
        $data->load([
            'masterpasien:rs1,rs2,rs17,rs16 as tgllahir',
            'poli:rs1,rs2',
            'dokter:nama,kdpegsimrs',
        ]);
        return new JsonResponse([
            'data' => $data,
        ]);
    }
    public function bukalayanan(Request $request)
    {
        $data = HomeCareKunjungan::select(
            'home_care_kunjungans.noreg',
            'home_care_kunjungans.norm',
            'memodiagnosadokter.diagnosa as memodiagnosa'
        )
            ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', 'home_care_kunjungans.noreg') // memo
            ->where('home_care_kunjungans.noreg', $request->noreg)
            ->first();
        if (!$data) return new JsonResponse(['message' => 'Mohon Maaf, Kunjungan pasien tidak ditemukan'], 410);
        $data->load([
            'newapotekrajal' => function ($q) {
                $q->with([
                    'dokter:nama,kdpegsimrs',
                    'permintaanresep.mobat:kd_obat,nama_obat,bentuk_sediaan,satuan_k,jenis_perbekalan',
                    'permintaanracikan.mobat:kd_obat,nama_obat,bentuk_sediaan,satuan_k,jenis_perbekalan',
                    'sistembayar'
                ])
                    ->orderBy('id', 'DESC');
            },

            'laborats' => function ($q) {
                $q->with('details.pemeriksaanlab')->orderBy('id', 'DESC')
                    ->where('unit_pengirim', '!=', 'POL014')
                    ->where('unit_pengirim', '!=', 'PEN005'); // tambahan HD
            },

            'laboratold' => function ($t) {
                $t->with('pemeriksaanlab')
                    ->orderBy('id', 'DESC');
            },

            'fisio' => function ($q) {
                $q->where('rs2', '!=', '')
                    ->groupBy('rs2')->orderBy('id', 'DESC');
            },
            'anamnesis',
            'tindakan' => function ($t) {
                $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs', 'pelaksanalamasimrs:nama,kdpegsimrs', 'gambardokumens:id,rs73_id,nama,original,url', 'mpoli:rs1,rs2')
                    ->orderBy('id', 'DESC');
            },
            'diagnosa' => function ($d) {
                $d->with('masterdiagnosa');
            },
        ]);

        return new JsonResponse($data);
    }
}
