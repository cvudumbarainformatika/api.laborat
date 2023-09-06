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
        $perencanaapembelianobat = Mobatnew::select(
            'kd_obat',
            'nama_obat',
            'status_generik',
            'status_fornas',
            'status_forkid',
            'sistembayar'
        )->with(
            [
                'stokrealgudang' => function ($stokrealgudang) {
                    $stokrealgudang->select(
                        'stokreal.kdobat',
                        DB::raw(
                            'sum(stokreal.jumlah) as jumlah'
                        )
                    )
                        ->whereIn(
                            'stokreal.kdruang',
                            ['Gd-03010100', 'Gd-03010101']
                        )
                        ->groupBy('stokreal.kdobat');
                },
                'stokrealallrs' => function ($stokrealallrs) {
                    $stokrealallrs->select(
                        'stokreal.kdobat',
                        DB::raw(
                            'sum(stokreal.jumlah) as jumlah'
                        )
                    )->groupBy('stokreal.kdobat');
                },
                'stokmaxrs' => function ($stokmaxrs) {
                    $stokmaxrs->select(
                        'min_max_ruang.kd_obat',
                        DB::raw(
                            'sum(min_max_ruang.max) as jumlah'
                        )
                    )->groupBy('min_max_ruang.kd_obat');
                },
                'perencanaanrinci' => function ($perencanaanrinci) {
                    $perencanaanrinci->select(
                        'kdobat',
                        DB::raw(
                            'sum(jumlahdirencanakan) as jumlah'
                        )
                    )->where('flag', '')
                        ->groupBy('kdobat');
                }
            ]
        )->get();

        return new JsonResponse($perencanaapembelianobat);
    }

    public function simpanrencanabeliobat(Request $request)
    {
        $cekflag = RencanabeliR::select('flag')->where('kdobat', $request->kdobat)->first();
        $flag = $cekflag->flag;
        if ($flag === '') {
            return new JsonResponse(['message' => 'maaf obat ini masih dalam proses pemesanan...!!!']);
        }

        if ($request->norencanabeliobat === '' || $request->norencanabeliobat === null) {
            //return('wew');
            DB::connection('farmasi')->select('call rencana_beliobat(@nomor)');
            $x = DB::connection('farmasi')->table('conter')->select('rencblobat')->get();
            $wew = $x[0]->rencblobat;
            $norencanabeliobat = FormatingHelper::norencanabeliobat($wew, 'REN-BOBAT');
            //return('wew');
            $simpanheder = RencanabeliH::create(
                [
                    'no_rencbeliobat' => $norencanabeliobat,
                    'tgl' => date('Y-m-d'),
                    'user' => auth()->user()->pegawai_id
                ]
            );

            if (!$simpanheder) {
                return new JsonResponse(['message' => 'not ok'], 500);
            }

            $simpanrinci = RencanabeliR::create(
                [
                    'no_rencbeliobat' => $norencanabeliobat,
                    'kdobat' => $request->kdobat,
                    'stok_real_gudang' => $request->stok_real_gudang,
                    'stok_real_rs'  => $request->stok_real_rs,
                    'stok_max_rs'  => $request->stok_max_rs,
                    'jumlah_bisa_dibeli'  => $request->jumlah_bisa_dibeli,
                    'tgl_stok'  => $request->tgl_stok,
                    'pabrikan'  => $request->pabrikan,
                    'pbf'  => $request->pbf,
                    'jumlahdirencanakan'  => $request->jumlahdpesan,
                    'user'  => auth()->user()->pegawai_id
                ]
            );

            if (!$simpanrinci) {
                return new JsonResponse(['message' => 'not ok'], 500);
            }

            return new JsonResponse(
                [
                    'message' => 'ok',
                    'notrans' => $norencanabeliobat,
                    'heder' => $simpanheder,
                    'rinci' => $simpanrinci
                ],
                200
            );
        }
        $simpanrinci = RencanabeliR::updateOrCreate(
            ['no_rencbeliobat' => $request->norencanabeliobat, 'kdobat' => $request->kdobat],
            [
                'stok_real_gudang' => $request->stok_real_gudang,
                'stok_real_rs'  => $request->stok_real_rs,
                'stok_max_rs'  => $request->stok_max_rs,
                'jumlah_bisa_dibeli'  => $request->jumlah_bisa_dibeli,
                'tgl_stok'  => $request->tgl_stok,
                'pabrikan'  => $request->pabrikan,
                'pbf'  => $request->pbf,
                'jumlahdirencanakan'  => $request->jumlahdpesan,
                'user'  => auth()->user()->pegawai_id
            ]
        );

        if (!$simpanrinci) {
            return new JsonResponse(['message' => 'not ok'], 500);
        }

        return new JsonResponse(
            [
                'message' => 'ok',
                'rinci' => $simpanrinci
            ],
            200
        );
    }

    public function listrencanabeli()
    {
        $rencanabeli = RencanabeliH::with('rincian')->where('no_rencbeliobat', 'LIKE', '%' . request('no_rencbeliobat') . '%')
            ->orderBy('tgl', 'desc')->paginate(request('per_page'));
        return new JsonResponse($rencanabeli);
    }

    public function kuncirencana(Request $request)
    {
        $kunci = RencanabeliH::where('no_rencbeliobat', $request->no_rencbeliobat)
            ->update(['flag' => 1]);
        if (!$kunci) {
            return new JsonResponse(['message' => 'gagal mengupdate data'], 500);
        }
        return new JsonResponse(['message' => 'ok'], 200);
    }
}
