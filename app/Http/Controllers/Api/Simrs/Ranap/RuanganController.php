<?php

namespace App\Http\Controllers\Api\Simrs\Ranap;

use App\Helpers\FormatingHelper;
use App\Helpers\TarifHelper;
use App\Http\Controllers\Controller;
use App\Models\Mutasi;
use App\Models\SerahTerima;
use App\Models\Simrs\Ranap\Mruangranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RuanganController extends Controller
{
    public function listruanganranap()
    {
        Cache::forget('ruanganranap_all_v2');

        $list = Cache::remember('ruanganranap_all_v2', now()->addDays(7), function () {
            return DB::table('rs24')
                ->select(
                    DB::raw('COALESCE(NULLIF(groups, ""), rs1) as kdruangan'),
                    DB::raw('COALESCE(NULLIF(groups_nama, ""), rs2) as ruang'),
                    'groups',
                    'groups_nama'
                )
                ->where('status', '<>', '1')
                ->where('rs4', '<>', 'BR')
                ->groupBy(DB::raw('COALESCE(NULLIF(groups, ""), rs1)'), DB::raw('COALESCE(NULLIF(groups_nama, ""), rs2)'), 'groups', 'groups_nama')
                ->orderBy('ruang', 'asc')
                ->get();
        });
        return new JsonResponse($list);
    }

    public function ruanganranap()
    {
        $ruangan = DB::table('v_15_23')->select('v_15_23.*', 'rs24.rs2 as titipan_ruang')
            ->leftJoin('rs24', 'rs24.rs1', '=', 'v_15_23.titipan')
            ->where('noreg', '=', request('noreg'))
            ->orderBy('noreg', 'desc')
            ->limit(10)->get();

        $tarip = TarifHelper::ruang(request('noreg'));
        $data = [
            'ruangan' => $ruangan,
            'tarif' => $tarip
        ];
        return new JsonResponse($data);
    }
}
