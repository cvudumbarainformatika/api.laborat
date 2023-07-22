<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliH;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliR;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerencanaanpembelianController extends Controller
{
    public function perencanaanpembelian()
    {
        $perencanaapembelianobat = Mobatnew::select('kd_obat','nama_obat')->with(
            [
                'stokrealgudang' => function($stokrealgudang)
                {
                    $stokrealgudang->select(
                        'stokreal.kdobat',DB::raw('sum(stokreal.jumlah) as jumlah'
                        ))
                        ->whereIn(
                            'stokreal.kdruang',['Gd-03010100','Gd-03010101']
                        )
                        ->groupBy('stokreal.kdobat');
                },
                'stokrealallrs' => function($stokrealallrs)
                {
                    $stokrealallrs->select(
                        'stokreal.kdobat',DB::raw('sum(stokreal.jumlah) as jumlah'
                        ))->groupBy('stokreal.kdobat');
                },
                'stokmaxrs' => function($stokmaxrs)
                {
                    $stokmaxrs->select(
                        'min_max_ruang.kd_obat',DB::raw('sum(min_max_ruang.max) as jumlah'
                        ))->groupBy('min_max_ruang.kd_obat');
                }
            ]
        )->get();
        return new JsonResponse($perencanaapembelianobat);
    }

    public function simpanrencanabeliobat_h(Request $request)
    {
        DB::connection('farmasi')->select('call rencana_beliobat(@nomor)');
        $x = DB::connection('farmasi')->table('conter')->select('rencblobat')->get();
        $wew = $x[0]->rencblobat;
        $norencanabeliobat = FormatingHelper::norencanabeliobat($wew, 'REN-BOBAT');

        $simpanheder = RencanabeliH::updateOrCreate(['no_rencbeliobat' => $norencanabeliobat],
        [
            'tgl' => date('Y-m-d'),
            'user' => auth()->user()->pegawai_id
        ]);

        return new JsonResponse(["MESSAGE" => "OK",$simpanheder], 200);
    }

    public function simpanrencanabeliobat_r(Request $request)
    {
        $simpanrinci = RencanabeliR::updateOrcreate(['kdobat' => $request->kdobat],
            [
                'stok_real_gudang' => $request->stok_real_gudang,
                'stok_real_rs'  => $request->stok_real_rs,
                'stok_max_rs'  => $request->stok_max_rs,
                'jumlah_bisa_dibeli'  => $request->jumlah_bisa_dibeli,
                'tgl_stok'  => $request->tgl_stok,
                'pabrikan'  => $request->pabrikan,
                'pbf'  => $request->pbf,
                'jumlahdpesan'  => $request->jumlahdpesan,
                'user'  => auth()->user()->pegawai_id
            ]);
        return new JsonResponse(["MESSAGE" => "OK",$simpanrinci], 200);
    }
}
