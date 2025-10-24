<?php

namespace App\Http\Controllers\Api\Simrs\HomeCare;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Homecare\HomeCareKunjungan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'tgl' => request('tgl') ?? null,
            'from' => request('from') ?? null,
            'to' => request('to') ?? null,
            'flag' => request('flag') ?? null,

        ];

        $raw = HomeCareKunjungan::query()
            ->when($req['q'], function ($q) use ($req) {
                $q->select('home_care_kunjungans.*')
                    ->leftJoin('rs15', 'rs15.rs1', '=', 'home_care_kunjungans.norm')
                    ->where(function ($y) use ($req) {
                        $y->where('rs15.rs2', 'LIKE', '%' . $req['q'] . '%')
                            ->orWhere('rs15.rs1', 'LIKE', '%' . $req['q'] . '%')
                            ->orWhere('home_care_kunjungans.noreg', 'LIKE', '%' . $req['q'] . '%');
                    });
            })
            ->when($req['tgl'], function ($q) use ($req) {
                $q->whereDate('tgl_kunjungan', $req['tgl']);
            })
            ->when($req['flag'], function ($q) use ($req) {
                $flag = strtolower($req['flag']);
                if ($flag == 'dalam pelayanan') $q->where('flag', '1');
                else if ($flag == 'terlayani') $q->where('flag', '2');
                else if ($flag == 'belum terlayani') $q->where(function ($y) {
                    $y->whereNull('flag')->orWhere('flag', '');
                });
            })
            ->when($req['from'], function ($q) use ($req) {
                $q->whereBetween('tgl_kunjungan', [$req['from'] . ' 00:00:00', $req['to'] . ' 23:59:59']);
            })
            ->with([
                'masterpasien:rs1,rs2,rs17,rs16 as tgllahir',
                'poli:rs1,rs2',
                'dokter:nama,kdpegsimrs',
            ]);
        $totalCount = (clone $raw)->count();
        $data = $raw->simplePaginate($req['per_page']);

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
    public function bukalayanan(Request $request) {}
}
